<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShopifyOrderImport;
use App\Models\Store;
use App\Models\User;
use App\Services\Shopify\OrderImport\ShopifyOrderBulkSource;
use App\Services\Shopify\OrderImport\ShopifyOrderImportPlan;
use App\Services\Shopify\OrderImport\ShopifyOrderImportRunner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ShopifyOrderImportTest extends TestCase
{
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'shopify_import_test',
            'database.connections.shopify_import_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
            'cache.default' => 'array',
            'shopify.store' => 'example-test',
            'shopify.version' => '2026-01',
            'shopify.token' => 'fake-test-token',
            'shopify.order_import_enabled' => false,
            'shopify.order_import_store_slug' => 'sorelle',
            'app.timezone' => 'America/Lima',
        ]);
        Http::preventStrayRequests();
        Sleep::fake();
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('store_user', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('districts', fn (Blueprint $table) => $table->unsignedBigInteger('id')->primary());
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');
            $table->string('name')->nullable();
            $table->string('primary')->nullable();
            $table->string('secondary')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('district_id')->constrained();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
        });
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('sku');
            $table->integer('stock')->default(50);
        });
        // Reproduce the old production schema using SQLite only.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_06_150000_prepare_shopify_order_import.php'))->up();
        DB::table('stores')->insert([
            ['id' => 1, 'name' => 'Sorelle', 'slug' => 'sorelle'],
            ['id' => 2, 'name' => 'Other', 'slug' => 'other'],
        ]);
        DB::table('products')->insert([['id' => 1, 'store_id' => 1], ['id' => 2, 'store_id' => 2]]);
        DB::table('variants')->insert([
            ['id' => 1, 'product_id' => 1, 'sku' => 'MATCH'],
            ['id' => 2, 'product_id' => 2, 'sku' => 'FOREIGN'],
            ['id' => 3, 'product_id' => 1, 'sku' => 'AMBIGUOUS'],
            ['id' => 4, 'product_id' => 1, 'sku' => 'AMBIGUOUS'],
        ]);
    }

    protected function tearDown(): void
    {
        Sleep::fake(false);
        foreach ($this->files as $file) {
            foreach ([$file, $file.'.download'] as $candidate) {
                if (is_file($candidate)) {
                    unlink($candidate);
                }
            }
        }
        DB::purge('shopify_import_test');
        parent::tearDown();
    }

    private function money(string $amount): array
    {
        return ['shopMoney' => ['amount' => $amount, 'currencyCode' => 'USD']];
    }

    private function order(int $id = 1): array
    {
        return [
            '__typename' => 'Order',
            'id' => 'gid://shopify/Order/'.$id,
            'name' => '#'.$id,
            'createdAt' => '2015-01-01T12:00:00Z',
            'updatedAt' => '2015-01-02T12:00:00Z',
            'displayFinancialStatus' => 'PAID',
            'displayFulfillmentStatus' => 'UNFULFILLED',
            'currentTotalPriceSet' => $this->money('121.99'),
            'currentSubtotalPriceSet' => $this->money('120.00'),
            'currentTotalTaxSet' => $this->money('0.00'),
            'totalShippingPriceSet' => $this->money('1.99'),
            'note' => 'Original note',
            'customAttributes' => [],
            'shippingAddress' => [
                'name' => 'Test Customer', 'address1' => 'Test street',
                'countryCodeV2' => 'US', 'city' => 'New York', 'zip' => '10001',
            ],
        ];
    }

    private function item(int $id, int $order = 1, string $sku = 'MATCH'): array
    {
        return [
            '__typename' => 'LineItem',
            '__parentId' => 'gid://shopify/Order/'.$order,
            'id' => 'gid://shopify/LineItem/'.$id,
            'name' => 'Historical product', 'sku' => $sku, 'quantity' => 1,
            'currentQuantity' => 1,
            'variant' => ['id' => 'gid://shopify/ProductVariant/100', 'sku' => $sku],
            'discountedUnitPriceSet' => $this->money('10.00'),
            'discountedTotalSet' => $this->money('10.00'),
        ];
    }

    private function source(array $records, int $orders, ?int $expectedRows = null): ShopifyOrderBulkSource
    {
        $source = Mockery::mock(ShopifyOrderBulkSource::class);
        $source->shouldReceive('verifyAccess')->once();
        $source->shouldReceive('start')->once()->andReturn('gid://shopify/BulkOperation/1');
        $source->shouldReceive('operation')->once()->andReturn([
            'status' => 'COMPLETED', 'rootObjectCount' => (string) $orders,
            'objectCount' => (string) ($expectedRows ?? count($records)), 'url' => 'https://example.test/signed-private-export',
        ]);
        $source->shouldReceive('download')->once()->andReturnUsing(function ($url, $path) use ($records) {
            file_put_contents($path, implode("\n", array_map(fn ($record) => json_encode($record, JSON_THROW_ON_ERROR), $records))."\n");
        });

        return $source;
    }

    private function start(ShopifyOrderImportRunner $runner): ShopifyOrderImport
    {
        $run = $runner->start(Store::findOrFail(1));
        $this->files[] = $runner->filePath($run);

        return $run;
    }

    public function test_default_command_and_disabled_execution_never_contact_shopify_or_write_orders(): void
    {
        $this->artisan('shopify:orders-import', ['store' => 'sorelle'])->assertSuccessful();
        $this->artisan('shopify:orders-import', ['store' => 'sorelle', '--execute' => true])->assertFailed();
        config(['shopify.order_import_enabled' => true]);
        $this->artisan('shopify:orders-import', ['store' => 'other', '--execute' => true])->assertFailed();
        Http::assertNothingSent();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('shopify_order_imports', 0);
        $this->assertTrue(app(ShopifyOrderImportPlan::class)->forStore(Store::find(1))['schema_ready']);
        Schema::drop('order_items');
        $plan = app(ShopifyOrderImportPlan::class)->forStore(Store::find(1));
        $this->assertFalse($plan['schema_ready']);
        $this->assertContains('order_items', $plan['missing_tables']);
        $this->artisan('shopify:orders-import', ['store' => 'sorelle', '--execute' => true])->assertFailed();
        Http::assertNothingSent();
    }

    public function test_schema_preparation_also_supports_the_full_orders_migration(): void
    {
        foreach (['order_items', 'order_shopify_details', 'shopify_order_import_rows', 'shopify_order_imports', 'orders'] as $table) {
            Schema::drop($table);
        }
        (require database_path('migrations/2025_10_04_035549_create_orders_table.php'))->up();
        (require database_path('migrations/2026_11_06_150000_prepare_shopify_order_import.php'))->up();
        $this->assertTrue(Schema::hasColumn('orders', 'currency_code'));
        $indexes = collect(Schema::getIndexes('orders'))->filter(fn ($index) => $index['unique']
            && $index['columns'] === ['store_id', 'shopify_order_id']);
        $this->assertCount(1, $indexes);
        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_import_keeps_all_lines_addresses_currency_and_dates_without_changing_stock(): void
    {
        $root = $this->order();
        $root['cancelledAt'] = '2015-01-03T12:00:00Z';
        // Children deliberately precede their root; more than the legacy ten lines.
        $records = [];
        for ($i = 1; $i <= 12; $i++) {
            $records[] = $this->item($i, 1, $i === 11 ? 'FOREIGN' : ($i === 12 ? 'AMBIGUOUS' : 'MATCH'));
        }
        $records[] = $root;
        $records[] = [
            '__typename' => 'ShippingLine', '__parentId' => $root['id'],
            'id' => 'gid://shopify/ShippingLine/1', 'title' => 'Original carrier',
        ];
        $runner = new ShopifyOrderImportRunner($this->source($records, 1));
        $run = $this->start($runner);
        $this->assertTrue($runner->advance($run));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 12);
        $this->assertDatabaseCount('addresses', 1);
        $order = Order::firstOrFail();
        $this->assertCount(12, $order->variants);
        $this->assertSame('USD', $order->currency_code);
        $this->assertSame('121.99', $order->total);
        $this->assertSame('cancelado', $order->status);
        $this->assertSame('2015-01-01 07:00:00', $order->getRawOriginal('created_at'));
        $this->assertDatabaseHas('order_items', ['sku' => 'FOREIGN', 'variant_id' => null]);
        $this->assertDatabaseHas('order_items', ['sku' => 'AMBIGUOUS', 'variant_id' => null]);
        $this->assertDatabaseHas('addresses', ['purpose' => 'shipping', 'district_id' => null, 'city' => 'New York']);
        $this->assertSame(2, (int) $run->fresh()->unmatched_variants);
        $this->assertSame(1, (int) $run->fresh()->unresolved_addresses);
        $details = json_decode(DB::table('order_shopify_details')->value('source_payload'), true);
        $this->assertSame('Original note', $details['note']);
        $this->assertSame('Original carrier', $details['shippingLines'][0]['title']);
        $this->assertSame([50, 50, 50, 50], DB::table('variants')->pluck('stock')->all());
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('users', 0);
        // A new complete run omits an existing Shopify order without duplicating children.
        $again = new ShopifyOrderImportRunner($this->source($records, 1));
        $secondRun = $this->start($again);
        $again->advance($secondRun);
        $this->assertSame(1, (int) $secondRun->fresh()->skipped_orders);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 12);
        Http::assertNothingSent();
    }

    public function test_each_order_is_atomic_and_failed_run_resumes_from_committed_checkpoint(): void
    {
        $bad = $this->item(2, 2);
        $bad['discountedTotalSet'] = $this->money('INVALID');
        $records = [$this->order(1), $this->item(1), $this->order(2), $bad];
        $runner = new ShopifyOrderImportRunner($this->source($records, 2));
        $run = $this->start($runner);
        try {
            $runner->advance($run);
            $this->fail('The invalid monetary amount must stop this order.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('importe', $exception->getMessage());
        }
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('addresses', 1);
        $this->assertSame('failed', $run->fresh()->status);
        $this->assertSame(1, (int) $run->fresh()->imported_orders);
        DB::table('shopify_order_import_rows')->where('import_id', $run->id)->where('source_id', $bad['id'])
            ->update(['payload' => json_encode($this->item(2, 2), JSON_THROW_ON_ERROR)]);
        $this->assertTrue($runner->advance($run->fresh()));
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertSame(2, (int) $run->fresh()->imported_orders);
        $this->assertSame('completed', $run->fresh()->status);
    }

    public function test_truncated_export_never_reaches_orders_even_when_root_count_matches(): void
    {
        $runner = new ShopifyOrderImportRunner($this->source([$this->order()], 1, 2));
        $run = $this->start($runner);
        try {
            $runner->advance($run);
            $this->fail('A missing child row must stop the import.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('todos los pedidos y artículos', $exception->getMessage());
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('failed', $run->fresh()->status);
        // Resume a locally repaired download without another API request or download.
        file_put_contents($runner->filePath($run), json_encode($this->item(1), JSON_THROW_ON_ERROR)."\n", FILE_APPEND);
        $this->assertTrue($runner->advance($run->fresh()));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_api_preparation_is_store_scoped_role_protected_and_read_only(): void
    {
        (require database_path('migrations/2025_11_03_071340_create_permission_tables.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['ceo', 'sales'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'api']);
        }
        $user = User::create(['name' => 'Manager']);
        $user->assignRole('ceo');
        $user->stores()->attach(1);
        $this->actingAs($user->fresh(), 'api');
        $this->getJson('/api/v1/sorelle/dashboard/migrations/shopify/orders')->assertOk()->assertJsonPath('data.enabled', false)->assertJsonCount(0, 'data.runs');
        $this->getJson('/api/v1/other/dashboard/migrations/shopify/orders')->assertForbidden();
        $user->syncRoles(['sales']);
        $this->actingAs($user->fresh(), 'api');
        $this->getJson('/api/v1/sorelle/dashboard/migrations/shopify/orders')->assertForbidden();
        Http::assertNothingSent();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('shopify_order_imports', 0);
    }

    public function test_source_requires_historical_access_and_exports_without_date_or_line_limits(): void
    {
        Http::fake(['example-test.myshopify.com/*' => Http::response(['data' => [
            'shop' => ['myshopifyDomain' => 'example-test.myshopify.com'],
            'currentAppInstallation' => ['accessScopes' => [['handle' => 'read_orders']]],
        ]])]);
        $source = new ShopifyOrderBulkSource;
        try {
            $source->verifyAccess();
            $this->fail('read_all_orders must be required before exporting.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('read_all_orders', $exception->getMessage());
        }
        $query = $source->exportQuery();
        $this->assertStringContainsString('lineItems {', $query);
        $this->assertStringNotContainsString('first:', $query);
        $this->assertStringNotContainsString('cancelled_at:', $query);
        $this->assertStringNotContainsString('created_at:', $query);
    }

    public function test_incomplete_bulk_operation_never_downloads_or_imports_partial_data(): void
    {
        $source = Mockery::mock(ShopifyOrderBulkSource::class);
        $source->shouldReceive('verifyAccess')->once();
        $source->shouldReceive('start')->once()->andReturn('gid://shopify/BulkOperation/1');
        $source->shouldReceive('operation')->once()->andReturn([
            'status' => 'FAILED', 'partialDataUrl' => 'https://example.test/partial',
        ]);
        $source->shouldNotReceive('download');
        $runner = new ShopifyOrderImportRunner($source);
        $run = $this->start($runner);
        try {
            $runner->advance($run);
            $this->fail('Partial results must not be imported.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('resultados parciales', $exception->getMessage());
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('shopify_order_import_rows', 0);
        $this->assertNull($run->fresh()->operation_id);
    }

    public function test_historical_write_scope_is_accepted_and_tokens_do_not_leak_into_downloads(): void
    {
        Http::fake([
            'example-test.myshopify.com/*' => Http::response(['data' => [
                'shop' => ['myshopifyDomain' => 'example-test.myshopify.com'],
                'currentAppInstallation' => ['accessScopes' => [['handle' => 'write_orders'], ['handle' => 'read_all_orders']]],
            ]]),
            'example.test/*' => Http::response(''),
        ]);
        $source = new ShopifyOrderBulkSource;
        $source->verifyAccess();
        $path = storage_path('app/private/shopify-order-import-test-'.uniqid().'.jsonl');
        $this->files[] = $path;
        $source->download('https://example.test/signed-file', $path);
        Http::assertSent(fn ($request) => $request->url() === 'https://example.test/signed-file'
            && ! $request->hasHeader('X-Shopify-Access-Token'));
    }

    public function test_throttled_requests_wait_for_retry_after_and_actual_point_recovery(): void
    {
        Http::fake(['example-test.myshopify.com/*' => Http::sequence()
            ->push([], 429, ['Retry-After' => '5'])
            ->push([
                'errors' => [['extensions' => ['code' => 'THROTTLED']]],
                'extensions' => ['cost' => [
                    'requestedQueryCost' => 20,
                    'throttleStatus' => ['currentlyAvailable' => 0, 'restoreRate' => 5],
                ]],
            ])
            ->push(['data' => ['node' => ['status' => 'RUNNING']]])]);

        $result = (new ShopifyOrderBulkSource)->operation('gid://shopify/BulkOperation/1');

        $this->assertSame('RUNNING', $result['status']);
        Http::assertSentCount(3);
        Sleep::assertSequence([Sleep::sleep(5), Sleep::sleep(5)]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_persistent_throttling_stops_after_five_attempts_without_an_infinite_loop(): void
    {
        Http::fake(['example-test.myshopify.com/*' => Http::response([], 429)]);
        try {
            (new ShopifyOrderBulkSource)->operation('gid://shopify/BulkOperation/1');
            $this->fail('The throttle retry loop must be bounded.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('cinco intentos', $exception->getMessage());
        }
        Http::assertSentCount(5);
        Sleep::assertSequence([Sleep::sleep(1), Sleep::sleep(2), Sleep::sleep(4), Sleep::sleep(8)]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_long_retry_after_never_triggers_an_early_retry(): void
    {
        foreach (['120', gmdate(DATE_RFC7231, time() + 120)] as $retryAfter) {
            Http::fake(['example-test.myshopify.com/*' => Http::response([], 429, ['Retry-After' => $retryAfter])]);
            try {
                (new ShopifyOrderBulkSource)->operation('gid://shopify/BulkOperation/1');
                $this->fail('A long retry delay must defer the import.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('mayor de 60 segundos', $exception->getMessage());
            }
            Http::assertSentCount(1);
        }
        Sleep::assertNeverSlept();
    }

    public function test_ambiguous_mutation_failure_is_not_retried(): void
    {
        Http::fake(['example-test.myshopify.com/*' => Http::response([], 500)]);
        try {
            (new ShopifyOrderBulkSource)->start();
            $this->fail('A server failure may hide a successful mutation and must not be retried.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 500', $exception->getMessage());
        }
        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_partial_mutation_result_with_errors_is_not_retried(): void
    {
        Http::fake(['example-test.myshopify.com/*' => Http::response([
            'data' => ['bulkOperationRunQuery' => ['bulkOperation' => ['id' => 'gid://shopify/BulkOperation/1']]],
            'errors' => [['extensions' => ['code' => 'THROTTLED']]],
        ])]);
        try {
            (new ShopifyOrderBulkSource)->start();
            $this->fail('A mutation with partial data must never be repeated automatically.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('rechazó la consulta', $exception->getMessage());
        }
        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }
}
