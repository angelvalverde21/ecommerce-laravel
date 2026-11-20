<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\ProductionStageDashboardController;
use App\Models\Manufacture;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionStagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'stages_test', 'database.connections.stages_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('store_user', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id'); $table->unsignedBigInteger('user_id');
        });
        Schema::create('manufactures', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('store_id'); $table->unsignedBigInteger('user_id')->nullable(); $table->string('type'); $table->string('name'); $table->timestamps();
        });
        Schema::create('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_address_id')->nullable();
            $table->id(); $table->unsignedBigInteger('store_id'); $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable(); $table->morphs('purchaseable'); $table->timestamps();
        });
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('purchase_id'); $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('name')->nullable(); $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('price', 12, 2)->nullable(); $table->decimal('subtotal', 12, 2)->nullable(); $table->timestamps();
        });
        Schema::create('units', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('payments', function (Blueprint $table) { $table->id(); $table->morphs('paymentable'); });
        DB::table('units')->insert(['id' => 1, 'name' => 'Unidad']);
        DB::table('stores')->insert(['id' => 1, 'name' => 'Sorelle', 'slug' => 'sorelle']);
        DB::table('manufactures')->insert([
            ['id' => 1, 'store_id' => 1, 'type' => 'production', 'name' => 'Anterior'],
            ['id' => 2, 'store_id' => 1, 'type' => 'order', 'name' => 'Compra'],
        ]);
        (require database_path('migrations/2026_11_10_100000_create_production_stages.php'))->up();
        (require database_path('migrations/2026_11_13_100000_create_delivery_tracking_tables.php'))->up();
        (require database_path('migrations/2026_11_13_120000_normalize_production_sections.php'))->up();
        (require database_path('migrations/2026_11_19_180000_add_reused_mold_to_production_progress.php'))->up();
        (require database_path('migrations/2026_11_20_130000_create_production_stage_attachments.php'))->up();
        (require database_path('migrations/2026_11_20_140000_convert_stage_attachments_to_polymorphic.php'))->up();
        $user = User::create(['name' => 'Responsable']);
        DB::table('store_user')->insert(['store_id' => 1, 'user_id' => $user->id]);
        $this->actingAs($user, 'api');
        Route::get('/stage-test/{production}', fn ($production) => app(ProductionStageDashboardController::class)->index(Store::findOrFail(1), $production));
        Route::post('/stage-test/{production}/classify', fn ($production) => app(ProductionStageDashboardController::class)->classifyPurchases(Store::findOrFail(1), $production, app(\App\Services\Dashboard\ProductionPurchaseClassifier::class)));
        Route::put('/stage-test/{production}/{stage}', fn (Request $request, $production, $stage) => app(ProductionStageDashboardController::class)->update(Store::findOrFail(1), $request, $production, $stage));
        Route::put('/stage-test/{production}/{stage}/purchases/{purchase}', fn (Request $request, $production, $stage, $purchase) => app(ProductionStageDashboardController::class)->assignPurchase(Store::findOrFail(1), $request, $production, $stage, $purchase));
        Route::get('/attachment-test/{production}/{stage}', fn ($production, $stage) => app(ProductionStageDashboardController::class)->attachments(Store::findOrFail(1), $production, $stage));
        Route::post('/attachment-test/{production}/{stage}', fn (Request $request, $production, $stage) => app(ProductionStageDashboardController::class)->uploadAttachment(Store::findOrFail(1), $request, $production, $stage));
        Route::get('/attachment-test/{production}/{stage}/{attachment}', fn ($production, $stage, $attachment) => app(ProductionStageDashboardController::class)->downloadAttachment(Store::findOrFail(1), $production, $stage, $attachment));
        Route::post('/purchase-stage-test', fn (Request $request) => responseOk(app(\App\Services\Dashboard\Purchase\PurchaseService::class)->store(Store::findOrFail(1), $request)));
        Route::delete('/purchase-stage-test/{purchase}', fn ($purchase) => app(\App\Http\Controllers\Api\Dashboard\PurchaseDashboardController::class)->destroy(Store::findOrFail(1), (string) $purchase));
    }

    public function test_stage_attachments_are_scoped_and_reject_invalid_files(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $other = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otro lote']);
        $file = \Illuminate\Http\UploadedFile::fake()->create('molde.pdf', 64, 'application/pdf');
        $response = $this->post('/attachment-test/1/corte', ['file' => $file], ['Accept' => 'application/json'])->assertOk();
        $id = $response->json('data.id');
        $response->assertJsonPath('data.name', 'molde.pdf')->assertJsonMissingPath('data.path');
        $this->getJson('/attachment-test/1/corte')->assertOk()->assertJsonCount(1, 'data');
        $this->get('/attachment-test/1/corte/'.$id)->assertOk()->assertDownload('molde.pdf');
        $this->get('/attachment-test/1/muestras/'.$id)->assertNotFound();
        $this->get('/attachment-test/'.$other->id.'/corte/'.$id)->assertNotFound();
        $path = DB::table('attachments')->where('id', $id)->value('path');
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($path);
        $this->getJson('/attachment-test/2/corte')->assertNotFound();
        foreach ([\Illuminate\Http\UploadedFile::fake()->create('script.php', 1, 'text/plain'),
            \Illuminate\Http\UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')] as $invalid) {
            $this->post('/attachment-test/1/corte', ['file' => $invalid], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->assertSame(1, DB::table('attachments')->count());
    }

    public function test_polymorphic_migration_preserves_existing_files_ids_and_downloads(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $migration = require database_path('migrations/2026_11_20_140000_convert_stage_attachments_to_polymorphic.php');
        $migration->down();
        $stageId = DB::table('production_stages')->where('code', 'cutting')->value('id');
        $progress = Manufacture::findOrFail(1)->stageProgress()->firstOrCreate(['stage_id' => $stageId]);
        $path = 'production-stage-attachments/'.$progress->id.'/legacy.pdf';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, 'existing document');
        DB::table('production_stage_attachments')->insert([
            'id' => 77, 'progress_id' => $progress->id, 'name' => 'legacy.pdf', 'path' => $path,
            'size' => 17, 'mime_type' => 'application/pdf', 'uploaded_by' => auth('api')->id(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $migration->up();
        $this->assertFalse(Schema::hasTable('production_stage_attachments'));
        $this->assertDatabaseHas('attachments', ['id' => 77, 'attachable_type' => 'production_stage_progress', 'attachable_id' => $progress->id, 'path' => $path]);
        $attachment = $progress->attachments()->findOrFail(77);
        $this->assertInstanceOf(\App\Models\ProductionStageProgress::class, $attachment->attachable);
        $this->get('/attachment-test/1/corte/77')->assertOk()->assertDownload('legacy.pdf');
        $migration->down();
        $this->assertDatabaseHas('production_stage_attachments', ['id' => 77, 'progress_id' => $progress->id, 'path' => $path]);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($path);
    }

    public function test_other_polymorphic_owners_cannot_be_downloaded_through_a_stage(): void
    {
        $stageId = DB::table('production_stages')->where('code', 'cutting')->value('id');
        $progress = Manufacture::findOrFail(1)->stageProgress()->firstOrCreate(['stage_id' => $stageId]);
        $id = DB::table('attachments')->insertGetId([
            'attachable_type' => \App\Models\Manufacture::class, 'attachable_id' => $progress->id,
            'name' => 'other.pdf', 'path' => 'other.pdf', 'size' => 10,
        ]);
        $this->getJson('/attachment-test/1/corte')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/attachment-test/1/corte/'.$id)->assertNotFound();
        $migration = require database_path('migrations/2026_11_20_140000_convert_stage_attachments_to_polymorphic.php');
        try { $migration->down(); $this->fail('Rollback would lose other attachments'); }
        catch (\RuntimeException $error) { $this->assertStringContainsString('otros', $error->getMessage()); }
        $this->assertDatabaseHas('attachments', ['id' => $id]);
    }

    public function test_purchase_files_use_polymorphic_ownership_and_private_image_previews(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $controller = \App\Http\Controllers\Api\Dashboard\PurchaseAttachmentDashboardController::class;
        $files = app(\App\Services\Dashboard\AttachmentFileService::class);
        Route::get('/purchase-files-test/{purchase}', fn ($purchase) => app($controller)->index(Store::findOrFail(1), $purchase));
        Route::post('/purchase-files-test/{purchase}', fn (Request $request, $purchase) => app($controller)->store(Store::findOrFail(1), $request, $purchase, $files));
        Route::get('/purchase-files-test/{purchase}/{file}', fn ($purchase, $file) => app($controller)->download(Store::findOrFail(1), $purchase, $file, $files));
        Route::get('/purchase-files-test/{purchase}/{file}/preview', fn ($purchase, $file) => app($controller)->preview(Store::findOrFail(1), $purchase, $file, $files));
        $purchase = Manufacture::findOrFail(1)->purchases()->create(['store_id' => 1]);
        $other = Manufacture::findOrFail(1)->purchases()->create(['store_id' => 1]);
        DB::table('stores')->insert(['id' => 2, 'name' => 'Other', 'slug' => 'other']);
        $foreign = Manufacture::findOrFail(1)->purchases()->create(['store_id' => 2]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $upload = $this->post('/purchase-files-test/'.$purchase->id, [
            'file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('foto.png', $png),
            'attachable_type' => 'production_stage_progress', 'attachable_id' => $other->id,
        ], ['Accept' => 'application/json'])->assertOk();
        $id = $upload->json('data.id');
        $upload->assertJsonPath('data.attachable_type', \App\Models\Purchase::class)->assertJsonPath('data.attachable_id', $purchase->id)->assertJsonMissingPath('data.path');
        $this->assertInstanceOf(\App\Models\Purchase::class, $purchase->attachments()->first()->attachable);
        $this->getJson('/purchase-files-test/'.$purchase->id)->assertOk()->assertJsonCount(1, 'data');
        $this->get('/purchase-files-test/'.$purchase->id.'/'.$id)->assertOk()->assertDownload('foto.png');
        $this->get('/purchase-files-test/'.$purchase->id.'/'.$id.'/preview')->assertOk()->assertHeader('Content-Type', 'image/png')->assertStreamedContent($png);
        $this->get('/purchase-files-test/'.$other->id.'/'.$id)->assertNotFound();
        $this->get('/purchase-files-test/'.$other->id.'/'.$id.'/preview')->assertNotFound();
        $this->getJson('/purchase-files-test/'.$foreign->id)->assertNotFound();
        $this->get('/attachment-test/1/corte/'.$id)->assertNotFound();
        $pdf = $this->post('/purchase-files-test/'.$purchase->id, ['file' => \Illuminate\Http\UploadedFile::fake()->create('factura.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        $this->get('/purchase-files-test/'.$purchase->id.'/'.$pdf->json('data.id').'/preview')->assertUnprocessable();
        $this->post('/purchase-files-test/'.$purchase->id, ['file' => \Illuminate\Http\UploadedFile::fake()->create('script.php', 1, 'text/plain')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertSame(2, $purchase->attachments()->count());
        foreach ([['ploteo.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], ['ploteo.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation']] as [$name, $mime]) {
            $this->post('/purchase-files-test/'.$purchase->id, ['file' => \Illuminate\Http\UploadedFile::fake()->create($name, 10, $mime)], ['Accept' => 'application/json'])->assertOk();
        }
        Route::get('/purchase-file-count-test', fn () => app(\App\Http\Controllers\Api\Dashboard\ManufacturePurchaseDashboardController::class)->index(Store::findOrFail(1), 1));
        $list = collect($this->getJson('/purchase-file-count-test')->assertOk()->json('data'));
        $this->assertSame(4, $list->firstWhere('id', $purchase->id)['attachments_count']);
        $this->assertSame(0, $list->firstWhere('id', $other->id)['attachments_count']);

    }

    protected function tearDown(): void
    {
        DB::purge('stages_test');
        parent::tearDown();
    }

    public function test_catalog_has_seven_sections_and_new_productions_do_not_create_stage_blocks(): void
    {
        $this->assertSame(7, DB::table('production_stages')->count());
        $this->assertSame(6, DB::table('production_stages')->where('show_in_stepper', true)->count());
        $new = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Nueva']);
        $this->assertSame(0, $new->stageProgress()->count());
        $this->getJson('/stage-test/'.$new->id)->assertOk()->assertJsonCount(7, 'data')->assertJsonPath('data.6.slug', 'otros')->assertJsonPath('data.6.show_in_stepper', false);
        $this->assertSame(0, $new->stageProgress()->count());
        $this->putJson('/stage-test/'.$new->id.'/corte', ['revision' => 0, 'status' => 'in_progress'])->assertOk();
        $this->assertSame(1, $new->stageProgress()->count());
        $this->getJson('/stage-test/2')->assertNotFound();
    }

    public function test_samples_cannot_finish_without_quantity_and_approval(): void
    {
        $id = DB::table('production_stages')->where('code', 'samples')->value('id');
        Manufacture::findOrFail(1)->purchases()->create(['store_id' => 1, 'production_stage_id' => $id]);
        $this->putJson("/stage-test/1/$id", ['revision' => 0, 'status' => 'completed'])->assertUnprocessable();
        $this->putJson("/stage-test/1/$id", [
            'revision' => 0, 'status' => 'completed', 'sample_quantity' => 3, 'sample_review' => 'approved',
            'sample_details' => 'S, M y L', 'sample_photos' => ['https://example.com/sample.jpg'],
        ])->assertOk()->assertJsonPath('data.sample_quantity', 3)->assertJsonPath('data.revision', 1);
        $this->assertNotNull(DB::table('production_stage_progress')->where('stage_id', $id)->where('manufacture_id', 1)->value('reviewed_by'));
        $this->assertSame(1, DB::table('production_stage_events')->count());
    }

    public function test_completion_requires_a_purchase_from_the_same_production_and_stage(): void
    {
        $stageId = DB::table('production_stages')->where('code', 'cutting')->value('id');
        $production = Manufacture::findOrFail(1);
        $otherStage = DB::table('production_stages')->where('code', 'materials')->value('id');
        $production->purchases()->create(['store_id' => 1, 'production_stage_id' => $otherStage]);
        $other = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otra']);
        $other->purchases()->create(['store_id' => 1, 'production_stage_id' => $stageId]);
        $this->putJson("/stage-test/1/$stageId", ['revision' => 0, 'status' => 'completed'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame(0, DB::table('production_stage_events')->count());
        $production->purchases()->create(['store_id' => 1, 'production_stage_id' => $stageId]);
        $this->putJson("/stage-test/1/$stageId", ['revision' => 0, 'status' => 'completed'])
            ->assertOk()->assertJsonPath('data.status', 'completed')->assertJsonPath('data.revision', 1);
        $this->assertNotNull($production->stageProgress()->where('stage_id', $stageId)->first()->completed_at);
        $this->assertSame(1, DB::table('production_stage_events')->count());
    }

    public function test_dates_are_shared_audited_and_stale_edits_rejected(): void
    {
        $id = DB::table('production_stages')->where('code', 'materials')->value('id');
        Manufacture::findOrFail(1)->purchases()->create(['store_id' => 1, 'production_stage_id' => $id]);
        $this->putJson("/stage-test/1/$id", ['revision' => 0, 'status' => 'in_progress', 'responsible' => 'Taller Rosa', 'expected_end' => '2026-11-20'])->assertOk();
        $this->putJson("/stage-test/1/$id", ['revision' => 0, 'status' => 'completed'])->assertUnprocessable();
        $this->putJson("/stage-test/1/$id", ['revision' => 1, 'status' => 'in_progress', 'expected_end' => '2026-11-22'])->assertUnprocessable();
        $this->putJson("/stage-test/1/$id", ['revision' => 1, 'status' => 'in_progress', 'expected_end' => '2026-11-22', 'date_change_reason' => 'Entrega acordada con el taller'])->assertOk();
        $event = DB::table('production_stage_events')->orderByDesc('id')->first();
        $changes = json_decode($event->changes, true);
        $this->assertStringContainsString('2026-11-20', $changes['expected_end']['before']);
        $this->assertStringContainsString('2026-11-22', $changes['expected_end']['after']);
        $this->assertSame('in_progress', DB::table('production_stage_progress')->where('stage_id', $id)->where('manufacture_id', 1)->value('status'));
    }

    public function test_classification_starts_the_stage_without_completing_it(): void
    {
        $id = DB::table('production_stages')->where('code', 'materials')->value('id');
        $purchase = DB::table('purchases')->insertGetId(['store_id' => 1, 'purchaseable_type' => Manufacture::class, 'purchaseable_id' => 1]);
        $this->putJson("/stage-test/1/$id/purchases/$purchase")->assertOk()->assertJsonPath('data.production_stage_id', $id);
        $this->assertSame('in_progress', DB::table('production_stage_progress')->where('stage_id', $id)->where('manufacture_id', 1)->value('status'));
        $other = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otra']);
        $otherPurchase = $other->purchases()->create(['store_id' => 1]);
        $this->putJson("/stage-test/1/$id/purchases/{$otherPurchase->id}")->assertNotFound();
        $this->getJson('/stage-test/2')->assertNotFound();
    }

    public function test_automatic_classification_is_idempotent_and_preserves_manual_groups_and_amounts(): void
    {
        $production = Manufacture::findOrFail(1);
        $manualStage = DB::table('production_stages')->where('code', 'samples')->value('id');
        foreach (['Tela denim', 'Confección', 'Negro / Perla', 'Planchado'] as $index => $name) {
            $purchase = $production->purchases()->create(['store_id' => 1,
                'production_stage_id' => $index === 3 ? $manualStage : null]);
            $purchase->items()->create(['name' => $name, 'quantity' => 2, 'price' => 25, 'subtotal' => 50]);
        }
        $classifier = app(\App\Services\Dashboard\ProductionPurchaseClassifier::class);
        $preview = $classifier->classify($production, false);
        $this->assertSame(3, $preview['assigned']);
        $this->assertSame(3, $production->purchases()->whereNull('production_stage_id')->count());
        $result = $classifier->classify($production);
        $this->assertSame(3, $result['assigned']);
        $this->assertSame(0, $result['remaining']);
        $this->assertSame(
            DB::table('production_stages')->where('code', 'others')->value('id'),
            $production->purchases()->whereHas('items', fn ($query) => $query->where('name', 'Negro / Perla'))->first()->production_stage_id
        );
        $this->assertSame($manualStage, $production->purchases()->orderByDesc('id')->first()->production_stage_id);
        $this->assertEquals(200, DB::table('purchase_items')->sum('subtotal'));
        $this->assertSame(0, $production->stageProgress()->where('status', 'completed')->count());
        $this->assertSame(3, $production->stageProgress()->where('status', 'in_progress')->count());
        $this->assertSame(0, $classifier->classify($production)['assigned']);
        $this->postJson('/stage-test/1/classify')->assertOk()
            ->assertJsonPath('data.assigned', 0)->assertJsonPath('data.remaining', 0)->assertJsonCount(4, 'data.purchases');
        $this->postJson('/stage-test/2/classify')->assertNotFound();
    }

    public function test_new_purchase_requires_its_own_stage_and_persists_recalculated_subtotal(): void
    {
        $payload = [
            'purchaseable_type' => 'manufacture', 'purchaseable_id' => 1,
            'purchase_items' => [['name' => 'Ploteo', 'quantity' => 2, 'unit_id' => 1, 'price' => 10.50, 'subtotal' => 999]],
        ];
        $this->postJson('/purchase-stage-test', $payload)->assertUnprocessable();
        $other = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otra']);
        $payload['production_stage_id'] = 9999;
        $this->postJson('/purchase-stage-test', $payload)->assertUnprocessable();
        $payload['production_stage_id'] = DB::table('production_stages')->where('code', 'molds')->value('id');
        $this->postJson('/purchase-stage-test', $payload)->assertOk()->assertJsonPath('data.production_stage_id', $payload['production_stage_id']);
        $this->assertEquals(21, DB::table('purchase_items')->value('subtotal'));
        $this->assertSame('in_progress', DB::table('production_stage_progress')->where('stage_id', $payload['production_stage_id'])->where('manufacture_id', 1)->value('status'));
    }

    public function test_purchase_and_optional_delivery_are_saved_atomically(): void
    {
        $payload = [
            'purchaseable_type' => 'manufacture', 'purchaseable_id' => 1,
            'production_stage_id' => DB::table('production_stages')->where('code', 'samples')->value('id'),
            'purchase_items' => [['name' => 'Confección de muestra', 'quantity' => 1, 'unit_id' => 1, 'price' => 90, 'subtotal' => 90]],
            'delivery' => ['enabled' => true, 'handed_over_on' => '2026-11-01'],
        ];
        $this->postJson('/purchase-stage-test', $payload)->assertUnprocessable();
        $this->assertSame(0, DB::table('purchases')->count());
        $payload['delivery']['expected_on'] = '2026-10-31';
        $this->postJson('/purchase-stage-test', $payload)->assertUnprocessable();
        $this->assertSame(0, DB::table('purchases')->count());
        $this->assertSame(0, DB::table('purchase_items')->count());
        $payload['delivery']['expected_on'] = '2026-12-01';
        $this->postJson('/purchase-stage-test', $payload)->assertOk()->assertJsonPath('data.delivery_tracking.expected_on', '2026-12-01');
        $this->assertSame(1, DB::table('delivery_tracking_events')->count());
        $payload['delivery'] = ['enabled' => false];
        $this->postJson('/purchase-stage-test', $payload)->assertOk()->assertJsonPath('data.delivery_tracking', null);
        $this->assertSame(1, DB::table('delivery_trackings')->count());
    }

    public function test_others_accepts_purchases_without_creating_progress_or_enabling_delivery(): void
    {
        $other = DB::table('production_stages')->where('slug', 'otros')->first();
        $payload = [
            'purchaseable_type' => 'manufacture', 'purchaseable_id' => 1,
            'production_stage_id' => $other->id,
            'purchase_items' => [['name' => 'Servicio de estibadores', 'quantity' => 2, 'unit_id' => 1, 'price' => 35, 'subtotal' => 70]],
            'delivery' => ['enabled' => false],
        ];
        $this->postJson('/purchase-stage-test', $payload)->assertOk()
            ->assertJsonPath('data.production_stage_id', $other->id)->assertJsonPath('data.delivery_tracking', null);
        $this->assertEquals(70, DB::table('purchase_items')->sum('subtotal'));
        $this->assertSame(0, DB::table('production_stage_progress')->where('stage_id', $other->id)->count());
        $this->putJson('/stage-test/1/otros', ['revision' => 0, 'status' => 'completed'])->assertUnprocessable();
        $this->getJson('/stage-test/1')->assertOk()->assertJsonPath('data.6.status', null)
            ->assertJsonPath('data.6.delivery_tracking_default', false)
            ->assertJsonPath('data.6.purchases_count', 1);
        // Selecting a shared section still scopes purchase access to its own production.
        $production = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otra']);
        $foreign = $production->purchases()->create(['store_id' => 1]);
        $this->putJson('/stage-test/1/otros/purchases/'.$foreign->id)->assertNotFound();
    }

    public function test_normalization_preserves_history_and_remaps_owner_specific_purchase_ids(): void
    {
        $production = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otra']);
        $this->putJson('/stage-test/'.$production->id.'/moldes-y-ploteo', ['revision' => 0, 'status' => 'in_progress', 'responsible' => 'Taller Rosa'])->assertOk();
        $catalogId = DB::table('production_stages')->where('code', 'molds')->value('id');
        $progressId = $production->stageProgress()->value('id');
        $purchase = $production->purchases()->create(['store_id' => 1, 'production_stage_id' => $catalogId]);
        $purchase->items()->create(['name' => 'Ploteo', 'quantity' => 2, 'unit_id' => 1, 'price' => 10, 'subtotal' => 20]);
        $migration = require database_path('migrations/2026_11_13_120000_normalize_production_sections.php');
        $migration->down();
        $this->assertSame($progressId, DB::table('purchases')->where('id', $purchase->id)->value('production_stage_id'));
        $migration->up();
        $this->assertSame($catalogId, DB::table('purchases')->where('id', $purchase->id)->value('production_stage_id'));
        $this->assertEquals(20, DB::table('purchase_items')->sum('subtotal'));
        $this->assertSame('Taller Rosa', DB::table('production_stage_progress')->where('id', $progressId)->value('responsible'));
        $this->assertSame($progressId, DB::table('production_stage_events')->value('production_stage_id'));
        $foreign = collect(Schema::getForeignKeys('production_stage_events'))->first(fn ($key) => $key['columns'] === ['production_stage_id']);
        $this->assertSame('production_stage_progress', $foreign['foreign_table']);
        $this->getJson('/stage-test/'.$production->id)->assertOk()->assertJsonPath('data.0.events.0.changes.responsible.after', 'Taller Rosa');
    }

    public function test_purchase_lifecycle_drives_status_and_last_deletion_clears_completion(): void
    {
        $production = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Automática']);
        $stageId = DB::table('production_stages')->where('code', 'cutting')->value('id');
        $payload = ['purchaseable_type' => 'manufacture', 'purchaseable_id' => $production->id, 'production_stage_id' => $stageId,
            'purchase_items' => [['name' => 'Corte', 'quantity' => 1, 'unit_id' => 1, 'price' => 20, 'subtotal' => 20]]];
        $this->getJson('/stage-test/'.$production->id)->assertJsonPath('data.3.status', 'pending');
        $first = $this->postJson('/purchase-stage-test', $payload)->assertOk()->json('data.id');
        $this->getJson('/stage-test/'.$production->id)->assertJsonPath('data.3.status', 'in_progress');
        $this->assertSame(0, $production->stageProgress()->count());
        $this->putJson("/stage-test/{$production->id}/$stageId", ['revision' => 0, 'status' => 'completed'])
            ->assertOk()->assertJsonPath('data.status', 'completed');
        $second = $this->postJson('/purchase-stage-test', $payload)->assertOk()->json('data.id');
        $this->deleteJson('/purchase-stage-test/'.$second)->assertOk();
        $this->getJson('/stage-test/'.$production->id)->assertJsonPath('data.3.status', 'completed');
        $this->deleteJson('/purchase-stage-test/'.$first)->assertOk();
        $this->getJson('/stage-test/'.$production->id)->assertJsonPath('data.3.status', 'pending')->assertJsonPath('data.3.completed_at', null);
        $progress = $production->stageProgress()->where('stage_id', $stageId)->first();
        $this->assertSame('pending', $progress->status);
        $this->assertNull($progress->completed_at);
        $this->assertSame(2, $progress->events()->count());
        $this->postJson('/purchase-stage-test', $payload)->assertOk();
        $this->getJson('/stage-test/'.$production->id)->assertJsonPath('data.3.status', 'in_progress')->assertJsonPath('data.3.completed_at', null);
        $revision = $progress->fresh()->revision;
        $this->putJson("/stage-test/{$production->id}/$stageId", ['revision' => $revision, 'status' => 'pending'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');
    }

    public function test_reassigning_the_last_purchase_resets_source_and_starts_destination(): void
    {
        $production = Manufacture::findOrFail(1);
        $cutting = DB::table('production_stages')->where('code', 'cutting')->value('id');
        $materials = DB::table('production_stages')->where('code', 'materials')->value('id');
        $purchase = $production->purchases()->create(['store_id' => 1, 'production_stage_id' => $cutting]);
        $this->putJson("/stage-test/1/$cutting", ['revision' => 0, 'status' => 'completed'])->assertOk();
        $this->putJson("/stage-test/1/$materials/purchases/{$purchase->id}")->assertOk();
        $this->getJson('/stage-test/1')->assertJsonPath('data.3.status', 'pending')
            ->assertJsonPath('data.3.completed_at', null)->assertJsonPath('data.2.status', 'in_progress');
        $this->assertSame('pending', $production->stageProgress()->where('stage_id', $cutting)->value('status'));
    }

    public function test_ceo_progress_uses_the_same_purchase_based_status(): void
    {
        Schema::create('manufacture_variant', function (Blueprint $table) { $table->unsignedBigInteger('manufacture_id'); $table->unsignedBigInteger('variant_id'); $table->integer('quantity'); });
        Schema::create('kardexes', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('store_id'); $table->morphs('kardexable'); $table->unsignedBigInteger('variant_id'); $table->string('direction'); $table->integer('quantity'); $table->timestamp('created_at'); });
        $production = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'CEO']);
        $stageId = DB::table('production_stages')->where('code', 'cutting')->value('id');
        $production->purchases()->create(['store_id' => 1, 'production_stage_id' => $stageId]);
        $now = \Carbon\CarbonImmutable::now()->addDay();
        $result = app(\App\Services\Dashboard\ProductionProgressService::class)->forStore(Store::findOrFail(1), $now->subMonth(), $now);
        $row = collect($result['items'])->firstWhere('id', $production->id);
        $this->assertSame('in_progress', collect($row['stages'])->firstWhere('id', $stageId)['status']);
        $this->assertSame('pending', collect($row['stages'])->firstWhere('code', 'samples')['status']);
    }

    public function test_expansion_preserves_history_and_backfills_eight_unique_records(): void
    {
        $this->putJson('/stage-test/1/corte', ['revision' => 0, 'status' => 'pending', 'responsible' => 'Taller'])->assertOk();
        $before = DB::table('production_stage_progress')->where('manufacture_id', 1)->where('stage_id', 4)->first();
        $events = DB::table('production_stage_events')->get()->toJson();
        $migration = require database_path('migrations/2026_11_19_170000_expand_production_workflow.php');
        $migration->up();
        $migration->up();
        $this->assertSame(8, DB::table('production_stages')->count());
        $this->assertSame(8, Manufacture::find(1)->stageProgress()->count());
        $this->assertSame(0, Manufacture::find(2)->stageProgress()->count());
        $this->assertEquals($before, DB::table('production_stage_progress')->where('id', $before->id)->first());
        $this->assertSame($events, DB::table('production_stage_events')->get()->toJson());
        $this->getJson('/stage-test/1')->assertOk()->assertJsonCount(8, 'data')
            ->assertJsonPath('data.6.code', 'reception')->assertJsonPath('data.7.code', 'others')
            ->assertJsonPath('data.7.status', 'pending');
        $production = app(\App\Services\Dashboard\Crud\ManufactureProductionService::class)->store(Store::find(1), new Request(['name' => 'Ocho etapas']));
        $this->assertSame(8, $production->stageProgress()->count());
        app(\App\Services\Dashboard\ProductionStageService::class)->initialize($production);
        $this->assertSame(8, $production->stageProgress()->count());
    }

    public function test_sewing_can_complete_before_cutting_and_is_not_reset_when_cutting_reopens(): void
    {
        (require database_path('migrations/2026_11_19_170000_expand_production_workflow.php'))->up();
        $production = Manufacture::find(1);
        $cutting = DB::table('production_stages')->where('code', 'cutting')->value('id');
        $sewing = DB::table('production_stages')->where('code', 'sewing')->value('id');
        $production->purchases()->create(['store_id' => 1, 'production_stage_id' => $sewing]);
        $this->getJson('/stage-test/1')->assertJsonPath('data.4.locked', false)->assertJsonPath('data.4.status', 'in_progress')->assertJsonPath('data.3.status', 'pending');
        $this->putJson('/stage-test/1/costura', ['revision' => 0, 'status' => 'in_progress'])->assertOk();
        $this->putJson('/stage-test/1/costura', ['revision' => 1, 'status' => 'completed'])->assertOk();
        $production->purchases()->create(['store_id' => 1, 'production_stage_id' => $cutting]);
        $this->putJson('/stage-test/1/corte', ['revision' => 0, 'status' => 'completed'])->assertOk();
        $this->putJson('/stage-test/1/corte', ['revision' => 1, 'status' => 'in_progress'])->assertOk();
        app(\App\Services\Dashboard\ProductionStageStateService::class)->synchronize($production, [$cutting]);
        $this->getJson('/stage-test/1')->assertJsonPath('data.4.locked', false)->assertJsonPath('data.4.status', 'completed');
        $this->assertSame('completed', $production->stageProgress()->where('stage_id', $sewing)->value('status'));
        $this->assertSame(2, $production->stageProgress()->where('stage_id', $sewing)->value('revision'));
    }

    public function test_reception_and_others_have_independent_audited_status_without_purchases(): void
    {
        (require database_path('migrations/2026_11_19_170000_expand_production_workflow.php'))->up();
        $this->putJson('/stage-test/1/recepcion', ['revision' => 0, 'status' => 'in_progress'])->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->putJson('/stage-test/1/otros', ['revision' => 0, 'status' => 'completed'])->assertOk()->assertJsonPath('data.status', 'completed');
        $this->getJson('/stage-test/1')->assertJsonPath('data.6.status', 'in_progress')->assertJsonPath('data.7.status', 'completed');
        $this->putJson('/stage-test/1/recepcion', ['revision' => 0, 'status' => 'completed'])->assertUnprocessable()->assertJsonValidationErrors('revision');
        $this->putJson('/stage-test/2/recepcion', ['revision' => 0, 'status' => 'completed'])->assertNotFound();
        $this->assertSame(2, DB::table('production_stage_events')->count());
    }

    public function test_reused_mold_can_be_completed_without_a_new_purchase_and_remains_audited(): void
    {
        $this->putJson('/stage-test/1/moldes-y-ploteo', ['revision' => 0, 'status' => 'completed', 'reuses_mold' => true, 'notes' => 'Molde del lote anterior'])
            ->assertOk()->assertJsonPath('data.status', 'completed')->assertJsonPath('data.reuses_mold', true);
        $this->getJson('/stage-test/1')->assertJsonPath('data.0.status', 'completed')->assertJsonPath('data.0.reuses_mold', true);
        $production = Manufacture::find(1);
        $molds = DB::table('production_stages')->where('code', 'molds')->value('id');
        app(\App\Services\Dashboard\ProductionStageStateService::class)->synchronize($production, [$molds]);
        $this->assertSame('completed', $production->stageProgress()->where('stage_id', $molds)->value('status'));
        $event = json_decode(DB::table('production_stage_events')->first()->changes, true);
        $this->assertTrue($event['reuses_mold']['after']);
        $this->putJson('/stage-test/1/moldes-y-ploteo', ['revision' => 1, 'status' => 'completed', 'reuses_mold' => false])->assertUnprocessable();
        $this->putJson('/stage-test/1/corte', ['revision' => 0, 'status' => 'completed', 'reuses_mold' => true])->assertUnprocessable();
    }

    private function selectableSections(): void
    {
        (require database_path('migrations/2026_11_19_170000_expand_production_workflow.php'))->up();
        (require database_path('migrations/2026_11_20_100000_make_production_sections_selectable.php'))->up();
        Route::get('/section-selection/{production}', fn ($production) => app(ProductionStageDashboardController::class)->configuration(Store::find(1), $production));
        Route::put('/section-selection/{production}', fn (Request $request, $production) => app(ProductionStageDashboardController::class)->configure(Store::find(1), $request, $production));
        Route::post('/production-selection', fn (Request $request) => responseOk(app(\App\Services\Dashboard\Crud\ManufactureProductionService::class)->store(Store::find(1), $request)));
    }

    public function test_productions_own_their_selection_and_new_productions_default_to_eight(): void
    {
        $this->selectableSections();
        $defaultId = $this->postJson('/production-selection', ['name' => 'Todas'])->assertOk()->json('data.id');
        $this->getJson('/stage-test/'.$defaultId)->assertJsonCount(8, 'data');
        $ids = \App\Models\ProductionStage::where('code', '!=', 'samples')->pluck('id')->all();
        $selectedId = $this->postJson('/production-selection', ['name' => 'Sin muestras', 'stage_ids' => $ids])->assertOk()->json('data.id');
        $rows = $this->getJson('/stage-test/'.$selectedId)->assertJsonCount(7, 'data')->json('data');
        $this->assertNotContains('samples', array_column($rows, 'code'));
        $this->assertSame(7, Manufacture::find($selectedId)->stageProgress()->count());
        $this->assertSame(8, Manufacture::find($defaultId)->stageProgress()->count());
        $this->assertSame(8, Manufacture::find(1)->stageProgress()->where('is_enabled', true)->count());
        $this->assertSame(0, Manufacture::find(2)->stageProgress()->count());
    }

    public function test_removed_sections_are_archived_and_restored_with_history_and_concurrency_checks(): void
    {
        $this->selectableSections();
        $samples = \App\Models\ProductionStage::where('code', 'samples')->value('id');
        $all = \App\Models\ProductionStage::pluck('id')->all();
        $withoutSamples = array_values(array_diff($all, [$samples]));
        $this->putJson('/stage-test/1/muestras', ['status' => 'pending', 'revision' => 0, 'notes' => 'Conservar instrucciones'])->assertOk();
        $progressId = Manufacture::find(1)->stageProgress()->where('stage_id', $samples)->value('id');
        $this->putJson('/section-selection/1', ['stage_ids' => $withoutSamples, 'revision' => 0])->assertOk()->assertJsonPath('data.revision', 1);
        $this->getJson('/stage-test/1')->assertJsonCount(7, 'data');
        $this->putJson('/stage-test/1/muestras', ['status' => 'pending', 'revision' => 2])->assertUnprocessable();
        $this->assertSame('Conservar instrucciones', DB::table('production_stage_progress')->where('id', $progressId)->value('notes'));
        $this->putJson('/section-selection/1', ['stage_ids' => $all, 'revision' => 0])->assertUnprocessable()->assertJsonValidationErrors('revision');
        $this->putJson('/section-selection/1', ['stage_ids' => $all, 'revision' => 1])->assertOk();
        $this->getJson('/stage-test/1')->assertJsonCount(8, 'data')->assertJsonPath('data.1.notes', 'Conservar instrucciones');
        $this->assertSame($progressId, Manufacture::find(1)->stageProgress()->where('stage_id', $samples)->value('id'));
        $this->assertSame(3, DB::table('production_stage_events')->where('production_stage_id', $progressId)->count());
        $foreign = Manufacture::create(['store_id' => 2, 'type' => 'production', 'name' => 'Otra tienda']);
        $this->getJson('/section-selection/'.$foreign->id)->assertNotFound();
        $this->putJson('/section-selection/'.$foreign->id, ['stage_ids' => $all, 'revision' => 0])->assertNotFound();
    }

    public function test_sections_with_purchases_cannot_be_removed_and_sewing_can_be_selected_alone(): void
    {
        $this->selectableSections();
        $samples = \App\Models\ProductionStage::where('code', 'samples')->value('id');
        $all = \App\Models\ProductionStage::pluck('id')->all();
        Manufacture::find(1)->purchases()->create(['store_id' => 1, 'production_stage_id' => $samples]);
        $this->putJson('/section-selection/1', ['stage_ids' => array_values(array_diff($all, [$samples])), 'revision' => 0])->assertUnprocessable()->assertJsonValidationErrors('stage_ids');
        $this->getJson('/stage-test/1')->assertJsonCount(8, 'data');
        $sewing = \App\Models\ProductionStage::where('code', 'sewing')->value('id');
        foreach ([[], [$samples, $samples], [999999]] as $selection) {
            $this->postJson('/production-selection', ['name' => 'Inválida', 'stage_ids' => $selection])->assertUnprocessable();
        }
        $this->assertSame(2, Manufacture::count());
        $id = $this->postJson('/production-selection', ['name' => 'Solo costura', 'stage_ids' => [$sewing]])->assertOk()->json('data.id');
        $this->getJson('/stage-test/'.$id)->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'sewing')->assertJsonPath('data.0.locked', false);
        Manufacture::find($id)->purchases()->create(['store_id' => 1, 'production_stage_id' => $sewing]);
        $this->putJson('/stage-test/'.$id.'/costura', ['status' => 'completed', 'revision' => 0])->assertOk()->assertJsonPath('data.status', 'completed');
        Manufacture::find(1)->purchases()->create(['store_id' => 1, 'production_stage_id' => $sewing]);
        $cutting = \App\Models\ProductionStage::where('code', 'cutting')->value('id');
        $this->putJson('/section-selection/1', ['stage_ids' => array_values(array_diff($all, [$cutting])), 'revision' => 0])->assertOk();
    }

    public function test_purchases_and_classification_cannot_recreate_unselected_sections(): void
    {
        $this->selectableSections();
        $materials = \App\Models\ProductionStage::where('code', 'materials')->value('id');
        $samples = \App\Models\ProductionStage::where('code', 'samples')->value('id');
        $id = $this->postJson('/production-selection', ['name' => 'Solo materiales', 'stage_ids' => [$materials]])->json('data.id');
        $this->postJson('/purchase-stage-test', ['purchaseable_type' => 'manufacture', 'purchaseable_id' => $id, 'production_stage_id' => $samples,
            'purchase_items' => [['name' => 'Muestras', 'quantity' => 1, 'unit_id' => 1, 'price' => 10, 'subtotal' => 10]]])->assertUnprocessable();
        $production = Manufacture::find($id);
        $purchase = $production->purchases()->create(['store_id' => 1]);
        $purchase->items()->create(['name' => 'Sin coincidencia', 'quantity' => 1, 'price' => 10, 'subtotal' => 10]);
        $this->putJson("/stage-test/$id/$samples/purchases/{$purchase->id}")->assertUnprocessable();
        $result = app(\App\Services\Dashboard\ProductionPurchaseClassifier::class)->classify($production);
        $this->assertSame(0, $result['assigned']);
        $this->assertSame(1, $result['remaining']);
        $this->assertSame(1, $production->stageProgress()->count());
        $this->assertNull($purchase->fresh()->production_stage_id);
    }

    public function test_dashboard_lists_only_the_sections_owned_by_each_production(): void
    {
        $this->selectableSections();
        Schema::create('manufacture_variant', function (Blueprint $table) { $table->unsignedBigInteger('manufacture_id'); $table->unsignedBigInteger('variant_id'); $table->integer('quantity'); });
        Schema::create('kardexes', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('store_id'); $table->morphs('kardexable'); $table->unsignedBigInteger('variant_id'); $table->string('direction'); $table->integer('quantity'); $table->timestamp('created_at'); });
        $ids = \App\Models\ProductionStage::whereIn('code', ['materials', 'sewing', 'finishing'])->orderBy('position')->pluck('id')->all();
        $id = $this->postJson('/production-selection', ['name' => 'Dos secciones', 'stage_ids' => $ids])->assertOk()->json('data.id');
        DB::table('manufactures')->where('id', 1)->update(['created_at' => now()]);
        $now = \Carbon\CarbonImmutable::now()->addDay();
        $result = app(\App\Services\Dashboard\ProductionProgressService::class)->forStore(Store::find(1), $now->subMonth(), $now);
        $row = collect($result['items'])->firstWhere('id', $id);
        $this->assertSame($ids, array_column($row['stages'], 'id'));
        $this->assertFalse(collect($row['stages'])->firstWhere('code', 'sewing')['locked']);
        $this->assertCount(8, collect($result['items'])->firstWhere('id', 1)['stages']);
    }
}
