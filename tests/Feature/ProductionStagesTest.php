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
            $table->id(); $table->unsignedBigInteger('store_id'); $table->string('type'); $table->string('name'); $table->timestamps();
        });
        Schema::create('purchases', function (Blueprint $table) {
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
        $user = User::create(['name' => 'Responsable']);
        DB::table('store_user')->insert(['store_id' => 1, 'user_id' => $user->id]);
        $this->actingAs($user, 'api');
        Route::get('/stage-test/{production}', fn ($production) => app(ProductionStageDashboardController::class)->index(Store::findOrFail(1), $production));
        Route::post('/stage-test/{production}/classify', fn ($production) => app(ProductionStageDashboardController::class)->classifyPurchases(Store::findOrFail(1), $production, app(\App\Services\Dashboard\ProductionPurchaseClassifier::class)));
        Route::put('/stage-test/{production}/{stage}', fn (Request $request, $production, $stage) => app(ProductionStageDashboardController::class)->update(Store::findOrFail(1), $request, $production, $stage));
        Route::put('/stage-test/{production}/{stage}/purchases/{purchase}', fn (Request $request, $production, $stage, $purchase) => app(ProductionStageDashboardController::class)->assignPurchase(Store::findOrFail(1), $request, $production, $stage, $purchase));
        Route::post('/purchase-stage-test', fn (Request $request) => responseOk(app(\App\Services\Dashboard\Purchase\PurchaseService::class)->store(Store::findOrFail(1), $request)));
        Route::delete('/purchase-stage-test/{purchase}', fn ($purchase) => app(\App\Http\Controllers\Api\Dashboard\PurchaseDashboardController::class)->destroy(Store::findOrFail(1), (string) $purchase));
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
        $id = DB::table('production_stages')->where('code', 'sewing')->value('id');
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
}
