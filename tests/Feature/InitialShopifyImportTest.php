<?php

namespace Tests\Feature;

use App\Jobs\RunInitialShopifyImport;
use App\Models\ImportShopifyRun;
use App\Models\Store;
use App\Models\User;
use App\Services\Shopify\InitialImport\InitialImportQueries;
use App\Services\Shopify\InitialImport\InitialImportRunner;
use App\Services\Shopify\InitialImport\InitialImportService;
use App\Services\Shopify\InitialImport\InitialImportSource;
use App\Services\Shopify\InitialImport\InitialImportWriter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InitialShopifyImportTest extends TestCase
{
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'initial_import_test', 'database.connections.initial_import_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
            'cache.default' => 'array', 'shopify.store' => 'example-test', 'shopify.version' => '2026-01', 'shopify.initial_import_version' => '2026-01', 'shopify.token' => 'fake-token', 'shopify.initial_import_store_slug' => 'sorelle']);
        Http::preventStrayRequests(); Sleep::fake(); Queue::fake();
        Schema::create('stores', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('slug'); });
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); $table->boolean('status')->default(true); $table->timestamps(); });
        Schema::create('store_user', function (Blueprint $table) { $table->unsignedBigInteger('store_id'); $table->unsignedBigInteger('user_id'); });
        (require database_path('migrations/2025_11_03_071340_create_permission_tables.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['master', 'ceo', 'sales'] as $role) Role::create(['name' => $role, 'guard_name' => 'api']);
        (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
        (require database_path('migrations/2026_11_16_120000_create_import_shopify_staging_tables.php'))->up();
        (require database_path('migrations/2026_11_17_100000_create_import_shopify_event_attachments_table.php'))->up();
        (require database_path('migrations/2026_11_17_110000_add_timeline_audit_to_import_shopify_tasks.php'))->up();
        (require database_path('migrations/2026_11_17_120000_add_download_metadata_to_import_shopify_attachments.php'))->up();
        (require database_path('migrations/2026_11_17_130000_create_import_shopify_order_updates_table.php'))->up();
        DB::table('stores')->insert([['id' => 1, 'name' => 'Sorelle', 'slug' => 'sorelle'], ['id' => 2, 'name' => 'Other', 'slug' => 'other']]);
        foreach (['orders', 'products', 'variants', 'customers'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) { $blueprint->id(); $blueprint->string('sentinel'); });
            DB::table($table)->insert(['sentinel' => 'unchanged']);
        }
        $user = User::create(['name' => 'CEO']); $user->assignRole('ceo'); $user->stores()->attach(1); $this->actingAs($user->fresh(), 'api');
    }

    protected function tearDown(): void
    {
        Sleep::fake(false);
        foreach ($this->files as $path) foreach ([$path, $path.'.download'] as $file) if (is_file($file)) unlink($file);
        DB::purge('initial_import_test'); parent::tearDown();
    }

    private function runRecord(): ImportShopifyRun
    {
        return ImportShopifyRun::create(['id' => (string) Str::uuid(), 'store_id' => 1, 'requested_by' => 1, 'status' => 'queued', 'shop_domain' => 'example-test.myshopify.com', 'api_version' => '2026-01']);
    }

    private function comment(array $attachments): array
    {
        return ['id' => 'gid://shopify/CommentEvent/90', '__typename' => 'CommentEvent', 'createdAt' => '2026-10-01T12:34:56Z',
            'message' => '<p>Comprobante adjunto</p>', 'rawMessage' => 'Comprobante adjunto', 'edited' => true,
            'subject' => ['__typename' => 'Order', 'id' => 'gid://shopify/Order/1'], 'attachments' => $attachments];
    }

    public function test_timeline_preserves_comment_metadata_and_private_original_bytes_idempotently(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class);
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jmN8AAAAASUVORK5CYII=');
        $file = ['id' => 'gid://shopify/CommentEventAttachment/100', 'name' => 'pago.png', 'fileExtension' => 'png', 'size' => strlen($bytes), 'url' => 'https://cdn.shopify.com/receipts/original.png?signature=private'];
        $event = $this->comment([$file]);
        $writer->save($run, 'events', $event, 'gid://shopify/Order/1');
        $record = DB::table('import_shopify_event_attachments')->first();
        $this->files[] = $path = storage_path('app/private/import-shopify/'.$run->id.'/attachments/'.$record->task_key.'.original');
        Http::fake(function ($request, $options) use ($bytes) {
            $this->assertFalse($request->hasHeader('X-Shopify-Access-Token'));
            $this->assertFalse($options['allow_redirects']);
            file_put_contents($options['sink'], $bytes);
            return Http::response($bytes, 200, ['Content-Type' => 'image/png']);
        });
        app(\App\Services\Shopify\InitialImport\InitialImportAttachments::class)->archive($run, $record->task_key);
        $event['attachments'][0]['url'] .= '-renewed';
        $writer->save($run, 'events', $event, 'gid://shopify/Order/1');
        app(\App\Services\Shopify\InitialImport\InitialImportAttachments::class)->archive($run, $record->task_key);
        $this->assertSame($bytes, file_get_contents($path));
        $record = DB::table('import_shopify_event_attachments')->first();
        $this->assertSame(hash('sha256', $bytes), $record->sha256);
        $this->assertSame('downloaded', $record->status);
        $this->assertSame('gid://shopify/Order/1', $record->order_shopify_id);
        $this->assertSame($event['id'], $record->parent_shopify_id);
        $this->assertDatabaseCount('import_shopify_events', 1); $this->assertDatabaseCount('import_shopify_event_attachments', 1);
        $saved = json_decode(DB::table('import_shopify_events')->value('payload'), true);
        $this->assertSame($event['createdAt'], $saved['createdAt']);
        $this->assertSame($event['rawMessage'], $saved['rawMessage']); $this->assertTrue($saved['edited']);
        $this->assertSame('requires_read_users', $saved['_import']['author_status']);
        Http::assertSentCount(1);
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/shopify')->assertOk()
            ->assertJsonPath('data.runs.0.attachments.downloaded', 1)->assertJsonMissingPath('data.runs.0.payload');
    }

    public function test_missing_attachment_never_marks_import_complete_and_resume_retries_it(): void
    {
        $run = $this->runRecord(); $run->update(['access_verified' => true]);
        app(InitialImportWriter::class)->save($run, 'events', $this->comment([
            ['id' => 'gid://shopify/CommentEventAttachment/100', 'url' => 'https://cdn.shopify.com/missing?secret=never-log', 'size' => 50],
        ]), 'gid://shopify/Order/1');
        $record = DB::table('import_shopify_event_attachments')->first();
        $this->files[] = storage_path('app/private/import-shopify/'.$run->id.'/attachments/'.$record->task_key.'.original');
        Http::fake(['*' => Http::response('', 404)]);
        $runner = new InitialImportRunner(Mockery::mock(InitialImportSource::class), app(InitialImportWriter::class));
        $runner->tick($run);
        $this->assertDatabaseHas('import_shopify_tasks', ['status' => 'blocked']);
        $this->assertDatabaseHas('import_shopify_event_attachments', ['status' => 'failed']);
        (new RunInitialShopifyImport($run->id))->handle($runner);
        $this->assertSame('failed', $run->fresh()->status);
        $this->assertNull($run->fresh()->completed_at);
        $this->assertStringNotContainsString('secret', $run->fresh()->error_message);
        $this->postJson('/api/v1/sorelle/dashboard/initial-import/shopify/'.$run->id.'/resume')->assertOk();
        $this->assertDatabaseHas('import_shopify_tasks', ['status' => 'queued']);
    }

    public function test_cdn_image_is_verified_when_shopify_upload_size_differs_and_local_corruption_is_repaired(): void
    {
        $image = imagecreatetruecolor(4, 4); ob_start(); imagepng($image); $bytes = ob_get_clean(); imagedestroy($image);
        $run = $this->runRecord();
        app(InitialImportWriter::class)->save($run, 'events', $this->comment([
            ['id' => 'gid://shopify/CommentEventAttachment/100', 'url' => 'https://cdn.shopify.com/receipt.png', 'size' => strlen($bytes) + 100, 'image' => null],
        ]), 'gid://shopify/Order/1');
        $record = DB::table('import_shopify_event_attachments')->first();
        $this->files[] = $path = storage_path('app/private/import-shopify/'.$run->id.'/attachments/'.$record->task_key.'.original');
        Http::fake(function ($request, $options) use ($bytes) {
            file_put_contents($options['sink'], $bytes);
            return Http::response($bytes, 200, ['Content-Type' => 'image/png', 'Content-Length' => strlen($bytes)]);
        });
        $archive = app(\App\Services\Shopify\InitialImport\InitialImportAttachments::class);
        $archive->archive($run, $record->task_key);
        $record = DB::table('import_shopify_event_attachments')->first();
        $metadata = json_decode($record->download_metadata, true);
        $this->assertFalse($metadata['source_size_matches']); $this->assertSame(4, $metadata['width']);
        $this->assertSame(strlen($bytes), (int) $record->bytes);
        file_put_contents($path, 'changed'); clearstatcache(true, $path);
        $archive->archive($run, $record->task_key);
        $this->assertSame($bytes, file_get_contents($path));
        Http::assertSentCount(2);
    }

    public function test_truncated_attachment_and_redirect_outside_shopify_are_not_archived(): void
    {
        $run = $this->runRecord();
        app(InitialImportWriter::class)->save($run, 'events', $this->comment([
            ['id' => 'gid://shopify/CommentEventAttachment/100', 'url' => 'https://cdn.shopify.com/receipt', 'size' => 50],
        ]), 'gid://shopify/Order/1');
        $record = DB::table('import_shopify_event_attachments')->first();
        $this->files[] = $path = storage_path('app/private/import-shopify/'.$run->id.'/attachments/'.$record->task_key.'.original');
        Http::fake(function ($request, $options) { file_put_contents($options['sink'], 'truncated'); return Http::response('truncated'); });
        $archive = app(\App\Services\Shopify\InitialImport\InitialImportAttachments::class);
        try { $archive->archive($run, $record->task_key); $this->fail('Incomplete evidence must be rejected.'); }
        catch (\RuntimeException $error) { $this->assertStringContainsString('tamaño', $error->getMessage()); }
        $this->assertFileDoesNotExist($path); $this->assertFileDoesNotExist($path.'.download');
        Http::swap(new \Illuminate\Http\Client\Factory()); Http::preventStrayRequests();
        Http::fake(['https://cdn.shopify.com/*' => Http::response('', 302, ['Location' => 'https://127.0.0.1/private'])]);
        try { $archive->archive($run, $record->task_key); $this->fail('An external redirect must be rejected.'); }
        catch (\RuntimeException $error) { $this->assertStringContainsString('no reconocido', $error->getMessage()); }
        Http::assertSentCount(1);
        $this->assertDatabaseMissing('import_shopify_event_attachments', ['status' => 'downloaded']);
    }

    public function test_timeline_backfill_requeues_only_event_pages_and_keeps_previous_data(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class);
        $writer->save($run, 'orders', ['id' => 'gid://shopify/Order/1', 'note' => 'Original']);
        DB::table('import_shopify_tasks')->update(['status' => 'completed', 'cursor' => 'old-cursor', 'processed_rows' => 3]);
        $run->update(['status' => 'completed', 'completed_at' => now()]);
        $this->artisan('shopify:refresh-import-timeline', ['run' => $run->id])->assertSuccessful();
        $this->assertDatabaseHas('import_shopify_tasks', ['kind' => 'events', 'status' => 'queued', 'cursor' => null, 'processed_rows' => 0]);
        $this->assertDatabaseHas('import_shopify_tasks', ['kind' => 'order_items', 'status' => 'completed', 'cursor' => 'old-cursor', 'processed_rows' => 3]);
        $this->assertDatabaseCount('import_shopify_orders', 1); $this->assertSame(2, (int) $run->fresh()->timeline_version);
        Queue::assertPushed(RunInitialShopifyImport::class);
        foreach (['orders', 'products', 'variants', 'customers'] as $table) $this->assertDatabaseHas($table, ['sentinel' => 'unchanged']);
        Http::assertNothingSent();
    }

    public function test_timeline_does_not_request_protected_staff_fields_without_read_users(): void
    {
        Http::fake(['*graphql.json' => Http::sequence()
            ->push(['data' => ['currentAppInstallation' => ['accessScopes' => [['handle' => 'read_orders']]]]])
            ->push(['data' => ['node' => ['events' => ['edges' => [['node' => $this->comment([])]], 'pageInfo' => ['hasNextPage' => false]]]]])]);
        $page = (new InitialImportSource())->page('events', 'gid://shopify/Order/1', null);
        $this->assertCount(1, $page['edges']);
        Http::assertSent(fn ($request) => str_contains($request['query'], 'attachments { id name fileExtension size url') && !str_contains($request['query'], 'staffAuthor: author'));
    }

    public function test_timeline_reports_inaccessible_history_only_after_following_all_pages(): void
    {
        $run = $this->runRecord(); $run->update(['access_verified' => true]); $writer = app(InitialImportWriter::class);
        foreach ([1, 2] as $id) $writer->task($run->id, 'events', 'gid://shopify/Order/'.$id);
        // Use a basic event on page one so pagination can be validated independently of comments.
        $source = Mockery::mock(InitialImportSource::class);
        $source->shouldReceive('batch')->once()->andReturn([
            'gid://shopify/Order/1' => ['hasTimelineComment' => true, 'edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]],
            'gid://shopify/Order/2' => ['hasTimelineComment' => true, 'edges' => [['node' => ['id' => 'gid://shopify/BasicEvent/1', '__typename' => 'BasicEvent', 'message' => 'Created']]], 'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'next']],
        ]);
        $source->shouldReceive('page')->with('events', 'gid://shopify/Order/2', 'next')->once()->andReturn([
            'hasTimelineComment' => true, 'edges' => [['node' => $this->comment([])]], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'last'],
        ]);
        $runner = new InitialImportRunner($source, $writer); $runner->tick($run); $runner->tick($run->fresh());
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/shopify')->assertOk()->assertJsonPath('data.runs.0.timeline_unavailable_orders', 1);
        $metadata = json_decode(DB::table('import_shopify_tasks')->where('owner_id', 'gid://shopify/Order/2')->value('metadata'), true);
        $this->assertSame(1, $metadata['comment_count']); $this->assertSame('available', $metadata['availability']);
    }

    public function test_start_only_queues_staging_and_enforces_store_role_and_single_active_run(): void
    {
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/shopify')->assertOk()->assertJsonCount(0, 'data.runs');
        $response = $this->postJson('/api/v1/sorelle/dashboard/initial-import/shopify')->assertOk()->assertJsonPath('data.status', 'queued');
        $id = $response->json('data.id');
        $this->assertDatabaseCount('import_shopify_tasks', 4);
        Queue::assertPushed(RunInitialShopifyImport::class, fn ($job) => $job->runId === $id && $job->connection === 'import_shopify');
        $this->postJson('/api/v1/sorelle/dashboard/initial-import/shopify')->assertConflict();
        $this->getJson('/api/v1/other/dashboard/initial-import/shopify')->assertForbidden();
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/shopify')->assertOk()->assertJsonMissingPath('data.runs.0.shop_domain')->assertJsonMissingPath('data.runs.0.payload');
        $user = User::findOrFail(1); $user->syncRoles(['sales']); $this->actingAs($user->fresh(), 'api');
        $this->postJson('/api/v1/sorelle/dashboard/initial-import/shopify')->assertForbidden();
        foreach (['orders', 'products', 'variants', 'customers'] as $table) $this->assertDatabaseHas($table, ['sentinel' => 'unchanged']);
        Http::assertNothingSent();
    }

    public function test_bulk_roots_and_paged_details_keep_raw_data_and_checkpoints_without_touching_final_tables(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class); $writer->task($run->id, 'orders', 'root');
        $taskId = DB::table('import_shopify_tasks')->value('id');
        $source = Mockery::mock(InitialImportSource::class);
        $source->shouldReceive('verifyAll')->once(); $source->shouldReceive('startDataset')->with('orders')->once()->andReturn('gid://shopify/BulkOperation/1');
        $source->shouldReceive('operation')->once()->andReturn(['status' => 'COMPLETED', 'objectCount' => '1', 'rootObjectCount' => '1', 'url' => 'https://example.test/export']);
        $source->shouldReceive('download')->once()->andReturnUsing(function ($url, $path) {
            file_put_contents($path, json_encode(['__typename' => 'Order', 'id' => 'gid://shopify/Order/1', 'note' => 'Original note', 'displayFinancialStatus' => 'PAID', 'transactions' => [['id' => 'gid://shopify/OrderTransaction/2', 'amountSet' => ['shopMoney' => ['amount' => '19.99', 'currencyCode' => 'PEN']]]], 'fulfillments' => [['id' => 'gid://shopify/Fulfillment/3', 'trackingInfo' => [['number' => 'TRACK']]]], 'refunds' => [['id' => 'gid://shopify/Refund/4', 'note' => 'Refund note']], 'shippingAddress' => ['name' => 'Original customer', 'address1' => 'Original address']])."\n");
        });
        $pages = 0;
        $source->shouldReceive('page')->andReturnUsing(function ($kind, $owner, $cursor) use (&$pages) {
            if ($kind === 'order_items') {
                $pages++;
                if ($pages === 1) { $this->assertNull($cursor); return ['edges' => [['node' => ['id' => 'gid://shopify/LineItem/10', 'sku' => 'SKU', 'quantity' => 2]]], 'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'next-page']]; }
                $this->assertSame('next-page', $cursor);
                return ['edges' => [['node' => ['id' => 'gid://shopify/LineItem/11', 'quantity' => 3]]], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'last-page']];
            }
            if ($kind === 'discounts') return ['edges' => [['node' => ['index' => 0, '__typename' => 'DiscountCodeApplication', 'code' => 'SALE']]], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'done']];
            return ['edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]];
        });
        $runner = new InitialImportRunner($source, $writer); $this->files[] = $runner->path($run->id, $taskId);
        $done = false; for ($i = 0; $i < 25 && !$done; $i++) $done = $runner->tick($run->fresh());
        $this->assertTrue($done); $this->assertSame('completed', $run->fresh()->status);
        $this->assertDatabaseCount('import_shopify_order_items', 2);
        $this->assertDatabaseCount('import_shopify_transactions', 1);
        $this->assertDatabaseCount('import_shopify_fulfillments', 1);
        $this->assertDatabaseCount('import_shopify_refunds', 1);
        $this->assertDatabaseHas('import_shopify_discounts', ['shopify_id' => 'gid://shopify/Order/1#discount-0']);
        $payload = json_decode(DB::table('import_shopify_orders')->value('payload'), true); $this->assertSame('Original note', $payload['note']);
        foreach (['orders', 'products', 'variants', 'customers'] as $table) { $this->assertDatabaseCount($table, 1); $this->assertDatabaseHas($table, ['sentinel' => 'unchanged']); }
        Http::assertNothingSent();
    }

    public function test_failure_is_resumable_and_never_commits_a_partial_api_page(): void
    {
        $run = $this->runRecord(); $run->update(['access_verified' => true]);
        app(InitialImportWriter::class)->task($run->id, 'order_items', 'gid://shopify/Order/1');
        $source = Mockery::mock(InitialImportSource::class); $source->shouldReceive('page')->once()->andThrow(new \RuntimeException('THROTTLED: pausa necesaria'));
        (new RunInitialShopifyImport($run->id))->handle(new InitialImportRunner($source, app(InitialImportWriter::class)));
        $this->assertSame('failed', $run->fresh()->status);
        $this->assertDatabaseCount('import_shopify_order_items', 0);
        $this->assertNull(DB::table('import_shopify_tasks')->value('cursor'));
        $this->postJson('/api/v1/sorelle/dashboard/initial-import/shopify/'.$run->id.'/resume')->assertOk()->assertJsonPath('data.status', 'queued');
        Queue::assertPushed(RunInitialShopifyImport::class);
    }

    public function test_repeated_record_writes_are_idempotent_and_keep_different_runs_separate(): void
    {
        $writer = app(InitialImportWriter::class); $run = $this->runRecord();
        $record = ['id' => 'gid://shopify/ProductVariant/1', 'sku' => null];
        $writer->save($run, 'variants', $record, 'gid://shopify/Product/1');
        $writer->save($run, 'variants', $record, 'gid://shopify/Product/1');
        $this->assertDatabaseCount('import_shopify_variants', 1); $this->assertDatabaseCount('import_shopify_tasks', 1);
        $run->update(['status' => 'completed']); $other = $this->runRecord();
        $writer->save($other, 'variants', $record, 'gid://shopify/Product/1');
        $this->assertDatabaseCount('import_shopify_variants', 2);
    }

    public function test_incomplete_bulk_download_is_replaced_on_resume_without_committing_incomplete_data(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class); $writer->task($run->id, 'products', 'root');
        $source = Mockery::mock(InitialImportSource::class);
        $source->shouldReceive('verifyAll')->once(); $source->shouldReceive('startDataset')->once()->andReturn('gid://shopify/BulkOperation/1');
        $source->shouldReceive('operation')->twice()->andReturn(['status' => 'COMPLETED', 'objectCount' => '2', 'rootObjectCount' => '2', 'url' => 'https://example.test/export']);
        $downloads = 0;
        $source->shouldReceive('download')->twice()->andReturnUsing(function ($url, $path) use (&$downloads) {
            $downloads++;
            $contents = json_encode(['id' => 'gid://shopify/Product/1', '__typename' => 'Product'])."\n";
            if ($downloads === 2) $contents .= json_encode(['id' => 'gid://shopify/Product/2', '__typename' => 'Product'])."\n";
            file_put_contents($path, $contents);
        });
        $runner = new InitialImportRunner($source, $writer);
        $this->files[] = $runner->path($run->id, DB::table('import_shopify_tasks')->value('id'));
        $runner->tick($run);
        try { $runner->tick($run->fresh()); $this->fail('Incomplete export must stop.'); }
        catch (\RuntimeException $error) { $this->assertStringContainsString('incompleto', $error->getMessage()); }
        $this->assertDatabaseCount('import_shopify_products', 0);
        $this->assertNull(DB::table('import_shopify_tasks')->value('expected_rows'));
        $runner->tick($run->fresh());
        $this->assertDatabaseCount('import_shopify_products', 2);
    }

    public function test_reapplying_staging_schema_keeps_existing_records_intact(): void
    {
        $run = $this->runRecord(); app(InitialImportWriter::class)->save($run, 'customers', ['id' => 'gid://shopify/Customer/1', 'note' => 'Original']);
        (require database_path('migrations/2026_11_16_120000_create_import_shopify_staging_tables.php'))->up();
        $this->assertDatabaseCount('import_shopify_customers', 1);
        $this->assertDatabaseCount('import_shopify_runs', 1);
    }

    public function test_detail_batch_saves_multiple_owners_and_keeps_extra_pages_pending(): void
    {
        $run = $this->runRecord(); $run->update(['access_verified' => true]); $writer = app(InitialImportWriter::class);
        $writer->task($run->id, 'discounts', 'gid://shopify/Order/1'); $writer->task($run->id, 'discounts', 'gid://shopify/Order/2');
        $source = Mockery::mock(InitialImportSource::class);
        $source->shouldReceive('batch')->once()->andReturn([
            'gid://shopify/Order/1' => ['edges' => [['node' => ['index' => 0, 'code' => 'SALE']]], 'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'next']],
            'gid://shopify/Order/2' => ['edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]],
        ]);
        (new InitialImportRunner($source, $writer))->tick($run);
        $this->assertDatabaseHas('import_shopify_tasks', ['owner_id' => 'gid://shopify/Order/1', 'status' => 'running', 'cursor' => 'next']);
        $this->assertDatabaseHas('import_shopify_tasks', ['owner_id' => 'gid://shopify/Order/2', 'status' => 'completed']);
        $this->assertDatabaseCount('import_shopify_discounts', 1);
    }

    public function test_order_browser_filters_audit_and_keeps_available_events_and_order_details(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class);
        foreach ([1, 2, 3] as $id) $writer->save($run, 'orders', ['id' => 'gid://shopify/Order/'.$id, 'name' => '#10'.$id,
            'createdAt' => '2026-11-01T10:00:00Z', 'customer' => ['displayName' => 'Cliente '.$id],
            'note' => 'Nota original', 'currentTotalPriceSet' => ['shopMoney' => ['amount' => '99.90', 'currencyCode' => 'PEN']]]);
        DB::table('import_shopify_tasks')->where('kind', 'events')->where('owner_id', 'gid://shopify/Order/1')->update(['status' => 'completed', 'metadata' => json_encode(['availability' => 'unavailable_comments', 'has_timeline_comment' => true, 'comment_count' => 0])]);
        DB::table('import_shopify_tasks')->where('kind', 'events')->where('owner_id', 'gid://shopify/Order/2')->update(['status' => 'completed', 'metadata' => json_encode(['availability' => 'available', 'has_timeline_comment' => true, 'comment_count' => 1])]);
        $writer->save($run, 'events', ['id' => 'gid://shopify/BasicEvent/1', '__typename' => 'BasicEvent', 'createdAt' => '2026-11-01T10:00:00Z', 'message' => '<p>Pedido creado</p>'], 'gid://shopify/Order/1');
        $writer->save($run, 'events', $this->comment([]), 'gid://shopify/Order/2');
        $writer->save($run, 'order_items', ['id' => 'gid://shopify/LineItem/1', 'title' => 'Blusa', 'quantity' => 3], 'gid://shopify/Order/1');
        $order = DB::table('import_shopify_orders')->where('shopify_id', 'gid://shopify/Order/1')->value('id');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?timeline=unavailable')->assertOk()->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.name', '#101')->assertJsonPath('data.items.0.events_count', 1)->assertJsonPath('data.summary.unavailable', 1)->assertJsonPath('data.summary.total', 3)->assertJsonPath('data.summary.unchecked', 1);
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?timeline=with_comments&q=Cliente%202')->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.name', '#102');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$order)->assertOk()->assertJsonPath('data.order.payload.note', 'Nota original')->assertJsonPath('data.items.0.quantity', 3)->assertJsonPath('data.event_counts.comments', 0)->assertJsonPath('data.event_counts.system', 1);
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$order.'/events')->assertOk()->assertJsonPath('data.items.0.message', 'Pedido creado');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$order.'/events?type=comments')->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?timeline=anything')->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_packing_list_filters_statuses_and_batches_current_items_with_images(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class);
        foreach ([
            [1, 'PAID', 'UNFULFILLED', null], [2, 'PENDING', 'UNFULFILLED', null],
            [3, 'PAID', 'FULFILLED', null], [4, 'PAID', 'PARTIALLY_FULFILLED', null],
            [5, 'PAID', 'UNFULFILLED', '2026-11-17T10:00:00Z'],
            [6, 'PAID', 'ON_HOLD', null], [7, 'PAID', 'SCHEDULED', null],
        ] as [$number, $payment, $shipping, $cancelled]) {
            $writer->save($run, 'orders', ['id' => 'gid://shopify/Order/'.$number, 'name' => '#'.$number,
                'displayFinancialStatus' => $payment, 'displayFulfillmentStatus' => $shipping, 'cancelledAt' => $cancelled,
                'shippingAddress' => ['city' => 'Lima', 'province' => 'Lima']]);
        }
        $writer->save($run, 'variants', ['id' => 'gid://shopify/ProductVariant/1', 'image' => ['url' => 'https://cdn.shopify.com/packing.jpg']]);
        foreach ([1, 2, 3, 4] as $number) $writer->save($run, 'order_items', [
            'id' => 'gid://shopify/LineItem/'.$number, 'title' => 'Blusa', 'variantTitle' => 'Azul / M',
            'quantity' => 5, 'currentQuantity' => $number === 4 ? 0 : 2, 'variant' => ['id' => 'gid://shopify/ProductVariant/1'],
        ], 'gid://shopify/Order/1');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?work=ready')->assertOk()
            ->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.name', '#1')
            ->assertJsonPath('data.packing_summary.total', 7)->assertJsonPath('data.packing_summary.ready', 1)
            ->assertJsonPath('data.packing_summary.shipped', 1)->assertJsonPath('data.packing_summary.preparing', 1)
            ->assertJsonPath('data.items.0.packing_state', 'ready')->assertJsonPath('data.items.0.units_count', 6)
            ->assertJsonPath('data.items.0.item_count', 3)->assertJsonCount(3, 'data.items.0.packing_items')
            ->assertJsonPath('data.items.0.destination', 'Lima')
            ->assertJsonPath('data.items.0.packing_items.0.display_image.url_large', 'https://cdn.shopify.com/packing.jpg');
        foreach (['review' => '#2', 'shipped' => '#3', 'preparing' => '#4', 'cancelled' => '#5', 'on_hold' => '#6', 'scheduled' => '#7'] as $filter => $name) {
            $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?work='.$filter)->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.name', $name);
        }
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?work=ready&q=%232')->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?work=invalid')->assertUnprocessable();
        $this->assertSame(7, DB::table('import_shopify_orders')->count());
        $this->assertArrayNotHasKey('display_image', json_decode(DB::table('import_shopify_order_items')->value('payload'), true));
        Http::assertNothingSent();
    }

    public function test_order_step_events_expose_only_recorded_progress_timestamps(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class);
        $writer->save($run, 'orders', ['id' => 'gid://shopify/Order/1', 'createdAt' => '2026-11-14T21:00:00Z', 'updatedAt' => '2026-11-17T15:00:00Z']);
        foreach (['sale_success', 'fulfillment_pending', 'confirmed'] as $index => $action) {
            $writer->save($run, 'events', ['id' => 'gid://shopify/BasicEvent/'.$index, '__typename' => 'BasicEvent', 'action' => $action,
                'createdAt' => '2026-11-14T21:07:00Z', 'message' => 'Mensaje original'], 'gid://shopify/Order/1');
        }
        $writer->save($run, 'events', [...$this->comment([]), 'action' => 'sale_success'], 'gid://shopify/Order/1');
        $id = DB::table('import_shopify_orders')->where('run_id', $run->id)->value('id');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$id)->assertOk()->assertJsonCount(2, 'data.step_events')
            ->assertJsonPath('data.step_events.0.action', 'sale_success')->assertJsonPath('data.step_events.0.createdAt', '2026-11-14T21:07:00Z')
            ->assertJsonPath('data.step_events.1.action', 'fulfillment_pending')->assertJsonMissingPath('data.step_events.0.message');
        Http::assertNothingSent();
    }

    public function test_order_item_images_use_variant_then_product_gallery_and_remain_scoped_to_the_import(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class);
        $writer->save($run, 'orders', ['id' => 'gid://shopify/Order/1']);
        $writer->save($run, 'products', ['id' => 'gid://shopify/Product/5', 'featuredImage' => ['url' => 'https://cdn.shopify.com/product.png']]);
        $writer->save($run, 'products', ['id' => 'gid://shopify/Product/6']);
        $writer->save($run, 'variants', ['id' => 'gid://shopify/ProductVariant/25', 'product' => ['id' => 'gid://shopify/Product/5'], 'image' => ['url' => 'https://cdn.shopify.com/variant.png', 'altText' => 'Variante correcta']]);
        $writer->save($run, 'images', ['id' => 'gid://shopify/ImageSource/61', 'url' => 'https://cdn.shopify.com/gallery.png'], 'gid://shopify/Product/6');
        foreach ([
            ['id' => 'gid://shopify/LineItem/1', 'image' => ['url' => 'javascript:alert(1)'], 'variant' => ['id' => 'gid://shopify/ProductVariant/25']],
            ['id' => 'gid://shopify/LineItem/2', 'variant' => ['id' => 'gid://shopify/ProductVariant/26', 'product' => ['id' => 'gid://shopify/Product/5']]],
            ['id' => 'gid://shopify/LineItem/3', 'variant' => null],
            ['id' => 'gid://shopify/LineItem/4', 'variant' => ['id' => 'gid://shopify/ProductVariant/27', 'product' => ['id' => 'gid://shopify/Product/6']]],
        ] as $item) $writer->save($run, 'order_items', [...$item, 'quantity' => 1], 'gid://shopify/Order/1');
        $other = ImportShopifyRun::create(['id' => (string) Str::uuid(), 'store_id' => 2, 'status' => 'completed', 'shop_domain' => 'other.myshopify.com', 'api_version' => '2026-01']);
        $writer->save($other, 'variants', ['id' => 'gid://shopify/ProductVariant/25', 'image' => ['url' => 'https://cdn.shopify.com/wrong-store.png']]);
        $id = DB::table('import_shopify_orders')->where('run_id', $run->id)->value('id');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$id)->assertOk()
            ->assertJsonPath('data.items.0.display_image.url', 'https://cdn.shopify.com/variant.png')
            ->assertJsonPath('data.items.0.display_image.url_thumbnail', 'https://cdn.shopify.com/variant.png?width=128&height=152')
            ->assertJsonPath('data.items.0.display_image.url_large', 'https://cdn.shopify.com/variant.png')
            ->assertJsonPath('data.items.0.display_image.fallback_images.0.url_thumbnail', 'https://cdn.shopify.com/product.png?width=128&height=152')
            ->assertJsonPath('data.items.0.display_image.fallback_urls.0', 'https://cdn.shopify.com/product.png')
            ->assertJsonPath('data.items.1.display_image.url', 'https://cdn.shopify.com/product.png')
            ->assertJsonPath('data.items.2.display_image', null)
            ->assertJsonPath('data.items.3.display_image.url', 'https://cdn.shopify.com/gallery.png');
        $raw = json_decode(DB::table('import_shopify_order_items')->where('shopify_id', 'gid://shopify/LineItem/1')->value('payload'), true);
        $this->assertArrayNotHasKey('display_image', $raw); Http::assertNothingSent();
    }

    public function test_order_browser_and_private_evidence_are_scoped_to_store_and_run(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class);
        $writer->save($run, 'orders', ['id' => 'gid://shopify/Order/1', 'name' => '#ORIGINAL']);
        $writer->save($run, 'events', $this->comment([['id' => 'gid://shopify/CommentEventAttachment/1', 'name' => 'receipt.pdf', 'url' => 'https://cdn.shopify.com/receipt', 'size' => 4]]), 'gid://shopify/Order/1');
        $order = DB::table('import_shopify_orders')->value('id'); $file = DB::table('import_shopify_event_attachments')->first();
        $relative = 'import-shopify/'.$run->id.'/attachments/'.$file->task_key.'.original';
        $this->files[] = $path = storage_path('app/private/'.$relative);
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
        file_put_contents($path, '%PDF');
        DB::table('import_shopify_event_attachments')->where('id', $file->id)->update(['status' => 'downloaded', 'local_path' => $relative, 'bytes' => 4, 'mime_type' => 'application/pdf']);
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$order.'/attachments/'.$file->id)->assertOk()->assertDownload('receipt.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$order.'/events')->assertOk()->assertJsonPath('data.items.0.attachments.0.id', $file->id)->assertJsonMissingPath('data.items.0.attachments.0.local_path');
        $run->update(['status' => 'completed']); $copy = $this->runRecord();
        $writer->save($copy, 'orders', ['id' => 'gid://shopify/Order/1', 'name' => '#NEW-COPY']);
        $copyOrder = DB::table('import_shopify_orders')->where('run_id', $copy->id)->value('id');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?run_id='.$run->id)->assertOk()->assertJsonPath('data.items.0.name', '#ORIGINAL');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$copyOrder.'/events')->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$copyOrder.'/attachments/'.$file->id)->assertNotFound();
        $other = ImportShopifyRun::create(['id' => (string) Str::uuid(), 'store_id' => 2, 'status' => 'completed', 'shop_domain' => 'other.myshopify.com', 'api_version' => '2026-01']);
        $writer->save($other, 'orders', ['id' => 'gid://shopify/Order/1', 'name' => '#OTHER-STORE']);
        $otherOrder = DB::table('import_shopify_orders')->where('run_id', $other->id)->value('id');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders?run_id='.$other->id)->assertNotFound();
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$otherOrder)->assertNotFound();
        $this->getJson('/api/v1/other/dashboard/initial-import/orders')->assertForbidden();
        $user = User::findOrFail(1); $user->syncRoles(['sales']); $this->actingAs($user->fresh(), 'api');
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders')->assertForbidden();
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/'.$order.'/attachments/'.$file->id)->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_new_order_button_is_store_scoped_and_queues_only_one_operation_for_repeated_clicks(): void
    {
        $run = $this->runRecord(); $run->update(['status' => 'completed', 'completed_at' => now()]);
        $response = $this->postJson('/api/v1/sorelle/dashboard/initial-import/orders/updates', ['run_id' => $run->id])->assertOk()->assertJsonPath('data.status', 'queued');
        $id = $response->json('data.id');
        $this->postJson('/api/v1/sorelle/dashboard/initial-import/orders/updates', ['run_id' => $run->id])->assertOk()->assertJsonPath('data.id', $id);
        $this->assertDatabaseCount('import_shopify_order_updates', 1); Queue::assertPushed(RunInitialShopifyImport::class, 1);
        $this->assertDatabaseCount('import_shopify_tasks', 0); Http::assertNothingSent();
        $this->getJson('/api/v1/sorelle/dashboard/initial-import/orders/updates/'.$id)->assertOk()->assertJsonMissingPath('data.cursor');
        $this->getJson('/api/v1/other/dashboard/initial-import/orders/updates/'.$id)->assertForbidden();
        $this->postJson('/api/v1/other/dashboard/initial-import/orders/updates', ['run_id' => $run->id])->assertForbidden();
        $user = User::findOrFail(1); $user->syncRoles(['sales']); $this->actingAs($user->fresh(), 'api');
        $this->postJson('/api/v1/sorelle/dashboard/initial-import/orders/updates', ['run_id' => $run->id])->assertForbidden();
    }

    public function test_new_orders_are_imported_with_all_details_references_and_evidence_without_reimporting_existing_orders(): void
    {
        $run = $this->runRecord(); $writer = app(InitialImportWriter::class);
        $writer->save($run, 'orders', ['id' => 'gid://shopify/Order/1', 'note' => 'Keep original snapshot']);
        DB::table('import_shopify_tasks')->update(['status' => 'completed']); $run->update(['status' => 'completed']);
        $service = app(\App\Services\Shopify\InitialImport\InitialImportOrderUpdates::class);
        $update = $service->start(Store::find(1), $run->id, 1);
        $created = now()->subMinute()->toIso8601String();
        $new = ['id' => 'gid://shopify/Order/2', '__typename' => 'Order', 'createdAt' => $created, 'note' => 'New note', 'customer' => ['id' => 'gid://shopify/Customer/9'],
            'transactions' => [['id' => 'gid://shopify/OrderTransaction/1', 'receiptJson' => '{"proof":"kept"}']],
            'fulfillments' => [['id' => 'gid://shopify/Fulfillment/1']], 'refunds' => [['id' => 'gid://shopify/Refund/1']]];
        $source = Mockery::mock(InitialImportSource::class); $source->shouldReceive('verifyAll')->twice(); $source->shouldNotReceive('startDataset');
        $source->shouldReceive('newOrders')->withAnyArgs()->andReturn(
            ['nodes' => [['id' => 'gid://shopify/Order/1', 'createdAt' => $created, 'note' => 'Do not overwrite'], $new], 'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'next-orders']],
            ['nodes' => [$new], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'last-orders']],
            ['nodes' => [$new], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'last-orders']]);
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jmN8AAAAASUVORK5CYII=');
        Http::fake(function ($request, $options) use ($bytes) { file_put_contents($options['sink'], $bytes); return Http::response($bytes); });
        $source->shouldReceive('snapshots')->andReturnUsing(function ($kind, $owners) {
            $type = ['customer_snapshot' => 'Customer', 'product_snapshot' => 'Product', 'variant_snapshot' => 'ProductVariant'][$kind]; $records = [];
            foreach ($owners as $id) $records[$id] = ['id' => $id, '__typename' => $type, 'note' => 'Full referenced record', ...($type === 'ProductVariant' ? ['product' => ['id' => 'gid://shopify/Product/5']] : [])];
            return $records;
        });
        $source->shouldReceive('page')->andReturnUsing(function ($kind, $owner, $cursor) use ($bytes) {
            $nodes = match ($kind) {
                'order_items' => [['id' => 'gid://shopify/LineItem/'.($cursor ? '3' : '2'), 'quantity' => 1, 'variant' => ['id' => 'gid://shopify/ProductVariant/25', 'product' => ['id' => 'gid://shopify/Product/5']]]],
                'events' => [$this->comment([['id' => 'gid://shopify/CommentEventAttachment/1', 'name' => 'proof.png', 'url' => 'https://cdn.shopify.com/proof.png', 'size' => strlen($bytes)]])],
                'fulfillment_items' => [['id' => 'gid://shopify/FulfillmentLineItem/1', 'quantity' => 1]],
                'refund_items' => [['id' => 'gid://shopify/RefundLineItem/1', 'quantity' => 1]],
                'addresses' => [['id' => 'gid://shopify/MailingAddress/1', 'city' => 'Lima']],
                'images' => [['id' => 'gid://shopify/ImageSource/1', 'url' => 'https://cdn.shopify.com/product.png']],
                default => [],
            };
            return ['edges' => array_map(fn ($node) => ['node' => $node], $nodes), 'hasTimelineComment' => true,
                'pageInfo' => ['hasNextPage' => $kind === 'order_items' && !$cursor, 'endCursor' => $kind === 'order_items' && !$cursor ? 'next-items' : 'end']];
        });
        $runner = new InitialImportRunner($source, $writer);
        $finished = false;
        for ($i = 0; $i < 60 && !$finished; $i++) $finished = $runner->tick($run->fresh());
        $this->assertTrue($finished); $this->assertSame('completed', $update->fresh()->status); $this->assertSame(1, $update->fresh()->added_orders);
        $this->assertDatabaseCount('import_shopify_orders', 2); $this->assertDatabaseCount('import_shopify_order_items', 2);
        foreach (['customers', 'products', 'variants', 'transactions', 'fulfillments', 'refunds', 'fulfillment_items', 'refund_items', 'events', 'images', 'addresses'] as $entity) $this->assertDatabaseCount('import_shopify_'.$entity, 1);
        $file = DB::table('import_shopify_event_attachments')->first(); $this->files[] = storage_path('app/private/'.$file->local_path);
        $this->assertSame('downloaded', $file->status);
        $original = json_decode(DB::table('import_shopify_orders')->where('shopify_id', 'gid://shopify/Order/1')->value('payload'), true);
        $this->assertSame('Keep original snapshot', $original['note']);
        $repeat = $service->start(Store::find(1), $run->id, 1); $runner->tick($run->fresh()); $this->assertTrue($runner->tick($run->fresh()));
        $this->assertSame(0, $repeat->fresh()->added_orders); $this->assertDatabaseCount('import_shopify_orders', 2);
        foreach (['orders', 'products', 'variants', 'customers'] as $table) $this->assertDatabaseHas($table, ['sentinel' => 'unchanged']);
    }

    public function test_failed_new_order_search_resumes_its_cursor_without_advancing_the_successful_checkpoint(): void
    {
        $run = $this->runRecord(); $run->update(['status' => 'completed']);
        $service = app(\App\Services\Shopify\InitialImport\InitialImportOrderUpdates::class); $update = $service->start(Store::find(1), $run->id, 1);
        $source = Mockery::mock(InitialImportSource::class); $source->shouldReceive('verifyAll')->twice();
        $source->shouldReceive('newOrders')->once()->withAnyArgs()->andReturn(['nodes' => [['id' => 'gid://shopify/Order/2', 'createdAt' => now()->subMinute()->toIso8601String()]], 'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'saved-cursor']]);
        $runner = new InitialImportRunner($source, app(InitialImportWriter::class)); $runner->tick($run->fresh());
        $source->shouldReceive('newOrders')->once()->andThrow(new \RuntimeException('Network interrupted'));
        (new RunInitialShopifyImport($run->id))->handle($runner);
        $this->assertSame('failed', $update->fresh()->status); $this->assertSame('saved-cursor', $update->fresh()->cursor); $this->assertNull($update->fresh()->completed_at);
        $resumed = $service->start(Store::find(1), $run->id, 1); $this->assertSame($update->id, $resumed->id);
        $source->shouldReceive('newOrders')->once()->with(Mockery::any(), Mockery::any(), 'saved-cursor')->andReturn(['nodes' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]]);
        $source->shouldReceive('page')->andReturn(['edges' => [], 'hasTimelineComment' => false, 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]]);
        $done = false; for ($i = 0; $i < 15 && !$done; $i++) $done = $runner->tick($run->fresh());
        $this->assertTrue($done); $this->assertSame('completed', $update->fresh()->status); $this->assertSame(1, $update->fresh()->added_orders);
        $this->assertDatabaseCount('import_shopify_order_updates', 1); $this->assertDatabaseCount('import_shopify_orders', 1);
    }

    public function test_source_spaces_queries_and_respects_throttling_and_missing_scopes(): void
    {
        Http::fake(['*graphql.json' => Http::sequence()
            ->push(['data' => ['node' => ['lineItems' => ['edges' => [], 'pageInfo' => ['hasNextPage' => false]]]], 'extensions' => ['cost' => ['throttleStatus' => ['maximumAvailable' => 2000, 'currentlyAvailable' => 0, 'restoreRate' => 10]]]])
            ->push(['errors' => [['extensions' => ['code' => 'THROTTLED']]]], 429, ['Retry-After' => '2'])
            ->push(['data' => ['node' => ['lineItems' => ['edges' => [], 'pageInfo' => ['hasNextPage' => false]]]]])]);
        $source = new InitialImportSource(); $source->page('order_items', 'gid://shopify/Order/1', null); $source->page('order_items', 'gid://shopify/Order/1', null);
        Http::assertSentCount(3); Sleep::assertSleptTimes(3);
    }
}
