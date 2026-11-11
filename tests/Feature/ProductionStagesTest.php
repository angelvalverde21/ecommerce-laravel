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
        DB::table('units')->insert(['id' => 1, 'name' => 'Unidad']);
        DB::table('stores')->insert(['id' => 1, 'name' => 'Sorelle', 'slug' => 'sorelle']);
        DB::table('manufactures')->insert([
            ['id' => 1, 'store_id' => 1, 'type' => 'production', 'name' => 'Anterior'],
            ['id' => 2, 'store_id' => 1, 'type' => 'order', 'name' => 'Compra'],
        ]);
        (require database_path('migrations/2026_11_10_100000_create_production_stages.php'))->up();
        $user = User::create(['name' => 'Responsable']);
        DB::table('store_user')->insert(['store_id' => 1, 'user_id' => $user->id]);
        $this->actingAs($user, 'api');
        Route::get('/stage-test/{production}', fn ($production) => app(ProductionStageDashboardController::class)->index(Store::findOrFail(1), $production));
        Route::put('/stage-test/{production}/{stage}', fn (Request $request, $production, $stage) => app(ProductionStageDashboardController::class)->update(Store::findOrFail(1), $request, $production, $stage));
        Route::put('/stage-test/{production}/{stage}/purchases/{purchase}', fn (Request $request, $production, $stage, $purchase) => app(ProductionStageDashboardController::class)->assignPurchase(Store::findOrFail(1), $request, $production, $stage, $purchase));
        Route::post('/purchase-stage-test', fn (Request $request) => responseOk(app(\App\Services\Dashboard\Purchase\PurchaseService::class)->store(Store::findOrFail(1), $request)));
    }

    protected function tearDown(): void
    {
        DB::purge('stages_test');
        parent::tearDown();
    }

    public function test_existing_and_new_productions_have_six_stages_but_orders_do_not(): void
    {
        $this->assertSame(6, DB::table('production_stages')->where('manufacture_id', 1)->count());
        $this->assertSame(0, DB::table('production_stages')->where('manufacture_id', 2)->count());
        $new = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Nueva']);
        $this->assertSame(['molds', 'samples', 'materials', 'cutting', 'sewing', 'finishing'], $new->stages()->pluck('code')->all());
        $this->getJson('/stage-test/1')->assertOk()->assertJsonCount(6, 'data');
    }

    public function test_samples_cannot_finish_without_quantity_and_approval(): void
    {
        $id = DB::table('production_stages')->where('code', 'samples')->value('id');
        $this->putJson("/stage-test/1/$id", ['revision' => 0, 'status' => 'completed'])->assertUnprocessable();
        $this->putJson("/stage-test/1/$id", [
            'revision' => 0, 'status' => 'completed', 'sample_quantity' => 3, 'sample_review' => 'approved',
            'sample_details' => 'S, M y L', 'sample_photos' => ['https://example.com/sample.jpg'],
        ])->assertOk()->assertJsonPath('data.sample_quantity', 3)->assertJsonPath('data.revision', 1);
        $this->assertNotNull(DB::table('production_stages')->where('id', $id)->value('reviewed_by'));
        $this->assertSame(1, DB::table('production_stage_events')->count());
    }

    public function test_dates_are_shared_audited_and_stale_edits_rejected(): void
    {
        $id = DB::table('production_stages')->where('code', 'sewing')->value('id');
        $this->putJson("/stage-test/1/$id", ['revision' => 0, 'status' => 'in_progress', 'responsible' => 'Taller Rosa', 'expected_end' => '2026-11-20'])->assertOk();
        $this->putJson("/stage-test/1/$id", ['revision' => 0, 'status' => 'completed'])->assertUnprocessable();
        $this->putJson("/stage-test/1/$id", ['revision' => 1, 'status' => 'in_progress', 'expected_end' => '2026-11-22'])->assertUnprocessable();
        $this->putJson("/stage-test/1/$id", ['revision' => 1, 'status' => 'in_progress', 'expected_end' => '2026-11-22', 'date_change_reason' => 'Entrega acordada con el taller'])->assertOk();
        $event = DB::table('production_stage_events')->orderByDesc('id')->first();
        $changes = json_decode($event->changes, true);
        $this->assertStringContainsString('2026-11-20', $changes['expected_end']['before']);
        $this->assertStringContainsString('2026-11-22', $changes['expected_end']['after']);
        $this->assertSame('in_progress', DB::table('production_stages')->where('id', $id)->value('status'));
    }

    public function test_classification_preserves_purchase_and_does_not_complete_stage(): void
    {
        $id = DB::table('production_stages')->where('code', 'materials')->value('id');
        $purchase = DB::table('purchases')->insertGetId(['store_id' => 1, 'purchaseable_type' => Manufacture::class, 'purchaseable_id' => 1]);
        $this->putJson("/stage-test/1/$id/purchases/$purchase")->assertOk()->assertJsonPath('data.production_stage_id', $id);
        $this->assertSame('pending', DB::table('production_stages')->where('id', $id)->value('status'));
        $other = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otra']);
        $otherStage = $other->stages()->first()->id;
        $this->putJson("/stage-test/1/$otherStage/purchases/$purchase")->assertNotFound();
        $this->getJson('/stage-test/2')->assertNotFound();
    }

    public function test_new_purchase_requires_its_own_stage_and_persists_recalculated_subtotal(): void
    {
        $payload = [
            'purchaseable_type' => 'manufacture', 'purchaseable_id' => 1,
            'purchase_items' => [['name' => 'Ploteo', 'quantity' => 2, 'unit_id' => 1, 'price' => 10.50, 'subtotal' => 999]],
        ];
        $this->postJson('/purchase-stage-test', $payload)->assertUnprocessable();
        $other = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otra']);
        $payload['production_stage_id'] = $other->stages()->first()->id;
        $this->postJson('/purchase-stage-test', $payload)->assertUnprocessable();
        $payload['production_stage_id'] = DB::table('production_stages')->where('manufacture_id', 1)->where('code', 'molds')->value('id');
        $this->postJson('/purchase-stage-test', $payload)->assertOk()->assertJsonPath('data.production_stage_id', $payload['production_stage_id']);
        $this->assertEquals(21, DB::table('purchase_items')->value('subtotal'));
        $this->assertSame('pending', DB::table('production_stages')->where('id', $payload['production_stage_id'])->value('status'));
    }
}
