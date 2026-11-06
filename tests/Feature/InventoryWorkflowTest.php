<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\InventoryManagementDashboardController;
use App\Http\Controllers\Api\Dashboard\InventorySessionDashboardController;
use App\Models\{Inventory, InventorySession, Store, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Route, Schema};
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'workflow_test', 'database.connections.workflow_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        Schema::create('stores', fn (Blueprint $table) => $table->id());
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('store_user', function (Blueprint $table) { $table->id(); $table->integer('store_id'); $table->integer('user_id'); });
        DB::table('stores')->insert([['id' => 1], ['id' => 2]]);
        DB::table('users')->insert([['id' => 1, 'name' => 'Actual'], ['id' => 2, 'name' => 'Equipo'], ['id' => 3, 'name' => 'Ajeno']]);
        DB::table('store_user')->insert([['store_id' => 1, 'user_id' => 1], ['store_id' => 1, 'user_id' => 2], ['store_id' => 2, 'user_id' => 3]]);
        foreach (['branches', 'warehouses', 'locations'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) { $table->id(); $table->integer('store_id'); $table->string('name'); $table->string('code'); if ($name === 'warehouses') { $table->integer('branch_id'); $table->boolean('is_active')->default(true); } if ($name === 'locations') { $table->integer('warehouse_id'); $table->unsignedBigInteger('parent_id')->nullable(); $table->boolean('is_active')->default(true); } });
        }
        foreach (['2026_04_10_140559_create_inventories_table.php', '2026_11_03_092614_create_inventory_sessions_table.php', '2026_11_03_110000_align_inventory_workflow_columns.php'] as $file) (require database_path('migrations/'.$file))->up();
        Schema::create('variants', fn (Blueprint $table) => $table->id());
        foreach (['2026_11_03_120000_create_inventory_location_progress_table.php', '2026_11_03_120100_create_inventory_counts_table.php', '2026_11_03_120200_create_inventory_adjustments_table.php', '2026_11_04_160000_add_location_to_inventory_sessions.php', '2026_11_04_170000_fix_warehouse_on_inventory_sessions.php'] as $file) (require database_path('migrations/'.$file))->up();
        $this->actingAs(User::findOrFail(1));
        Route::get('/test-inventories', fn (Request $request) => app(InventoryManagementDashboardController::class)->index(Store::findOrFail(1), $request));
        Route::get('/test-options', fn () => app(InventoryManagementDashboardController::class)->options(Store::findOrFail(1)));
        Route::post('/test-inventories', fn (Request $request) => app(InventoryManagementDashboardController::class)->store(Store::findOrFail(1), $request));
        Route::put('/test-inventories/{id}', fn (Request $request, string $id) => app(InventoryManagementDashboardController::class)->update(Store::findOrFail(1), $request, $id));
        Route::get('/test-inventories/{id}/sessions', fn (string $id) => app(InventorySessionDashboardController::class)->index(Store::findOrFail(1), $id));
        Route::post('/test-inventories/{id}/sessions', fn (Request $request, string $id) => app(InventorySessionDashboardController::class)->store(Store::findOrFail(1), $request, $id));
        Route::put('/test-inventories/{id}/sessions/{session}', fn (Request $request, string $id, string $session) => app(InventorySessionDashboardController::class)->update(Store::findOrFail(1), $request, $id, $session));
    }

    private function inventory(int $store = 1): Inventory
    {
        return Inventory::create(['store_id' => $store, 'created_user_id' => 1, 'name' => 'Conteo', 'status' => 'draft']);
    }

    private function countingLocation(): int
    {
        $branch = DB::table('branches')->insertGetId(['store_id' => 1, 'name' => 'Central', 'code' => 'B']);
        $warehouse = DB::table('warehouses')->insertGetId(['store_id' => 1, 'branch_id' => $branch, 'name' => 'Tienda', 'code' => 'W']);
        return DB::table('locations')->insertGetId(['store_id' => 1, 'warehouse_id' => $warehouse, 'name' => 'Zona', 'code' => 'Z']);
    }

    public function test_inventory_uses_authenticated_creator_and_validates_its_scope(): void
    {
        $this->postJson('/test-inventories', ['name' => 'Noviembre', 'created_user_id' => 3, 'store_id' => 2, 'status' => 'completed'])
            ->assertCreated()->assertJsonPath('data.created_user_id', 1)->assertJsonPath('data.store_id', 1)->assertJsonPath('data.status', 'draft');
        DB::table('branches')->insert(['id' => 1, 'store_id' => 2, 'name' => 'Ajena', 'code' => 'X']);
        $this->postJson('/test-inventories', ['name' => 'Ajeno', 'inventoriable_type' => 'branch', 'inventoriable_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('inventoriable_id');
        $this->postJson('/test-inventories', ['name' => 'No válido', 'inventoriable_type' => 'App\\Models\\User', 'inventoriable_id' => 1])->assertUnprocessable();
    }

    public function test_session_starts_inventory_and_can_be_closed_before_completing(): void
    {
        $inventory = $this->inventory();
        $session = $this->postJson('/test-inventories/'.$inventory->id.'/sessions', ['warehouse_id' => DB::table('locations')->where('id', $this->countingLocation())->value('warehouse_id'), 'user_id' => 2, 'comment' => 'Primer turno'])->assertCreated()->assertJsonPath('data.status', 'open')->json('data.id');
        $this->assertSame('in_progress', $inventory->fresh()->status);
        $this->assertNotNull($inventory->fresh()->started_at);
        $this->putJson('/test-inventories/'.$inventory->id, ['name' => 'Conteo', 'status' => 'completed'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->putJson('/test-inventories/'.$inventory->id.'/sessions/'.$session, ['user_id' => 2, 'status' => 'closed'])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->assertNotNull(InventorySession::findOrFail($session)->ended_at);
        $this->putJson('/test-inventories/'.$inventory->id, ['name' => 'Conteo', 'status' => 'completed'])->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertNotNull($inventory->fresh()->completed_at);
        $this->postJson('/test-inventories/'.$inventory->id.'/sessions', ['warehouse_id' => DB::table('locations')->where('id', $this->countingLocation())->value('warehouse_id'), 'user_id' => 1])->assertUnprocessable();
        $this->putJson('/test-inventories/'.$inventory->id, ['name' => 'Conteo', 'status' => 'draft'])->assertUnprocessable();
    }

    public function test_cancellation_closes_open_sessions(): void
    {
        $inventory = $this->inventory();
        $this->postJson('/test-inventories/'.$inventory->id.'/sessions', ['warehouse_id' => DB::table('locations')->where('id', $this->countingLocation())->value('warehouse_id')])->assertCreated();
        $this->putJson('/test-inventories/'.$inventory->id, ['name' => 'Conteo', 'status' => 'cancelled'])->assertOk();
        $session = $inventory->sessions()->firstOrFail();
        $this->assertSame('cancelled', $session->status);
        $this->assertNotNull($session->ended_at);
    }

    public function test_sessions_reject_another_store_and_duplicate_open_operator(): void
    {
        $inventory = $this->inventory();
        $this->postJson('/test-inventories/'.$inventory->id.'/sessions', ['warehouse_id' => DB::table('locations')->where('id', $this->countingLocation())->value('warehouse_id'), 'user_id' => 3])->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->postJson('/test-inventories/'.$inventory->id.'/sessions', ['warehouse_id' => DB::table('locations')->where('id', $this->countingLocation())->value('warehouse_id'), 'user_id' => 1])->assertCreated();
        $this->postJson('/test-inventories/'.$inventory->id.'/sessions', ['warehouse_id' => DB::table('locations')->where('id', $this->countingLocation())->value('warehouse_id'), 'user_id' => 1])->assertUnprocessable();
        $other = $this->inventory(2);
        $this->getJson('/test-inventories/'.$other->id.'/sessions')->assertNotFound();
        $this->postJson('/test-inventories/'.$other->id.'/sessions', [])->assertNotFound();
        $this->getJson('/test-inventories')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_sessions_cannot_be_reopened_or_accessed_under_another_inventory(): void
    {
        $one = $this->inventory(); $two = $this->inventory();
        $id = $this->postJson('/test-inventories/'.$one->id.'/sessions', ['warehouse_id' => DB::table('locations')->where('id', $this->countingLocation())->value('warehouse_id')])->assertCreated()->json('data.id');
        $this->putJson('/test-inventories/'.$two->id.'/sessions/'.$id, ['user_id' => 1, 'status' => 'closed'])->assertNotFound();
        $this->putJson('/test-inventories/'.$one->id.'/sessions/'.$id, ['user_id' => 1, 'status' => 'cancelled'])->assertOk();
        $this->putJson('/test-inventories/'.$one->id.'/sessions/'.$id, ['user_id' => 1, 'status' => 'open'])->assertUnprocessable();
    }

    public function test_catalog_filters_and_operator_options(): void
    {
        $this->inventory()->update(['name' => 'Noviembre']);
        $this->inventory()->update(['name' => 'Octubre', 'status' => 'cancelled']);
        $this->getJson('/test-inventories?search=Noviembre&status=draft')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/test-options')->assertOk()->assertJsonCount(2, 'data.operators');
    }

    public function test_existing_inventory_records_are_preserved_by_upgrade(): void
    {
        Schema::drop('inventory_adjustments');
        Schema::drop('inventory_counts');
        Schema::drop('inventory_location_progress');
        Schema::drop('inventory_sessions');
        Schema::drop('inventories');
        Schema::create('inventories', function (Blueprint $table) { $table->id(); $table->integer('user_id'); $table->integer('store_id'); $table->string('name'); });
        DB::table('inventories')->insert(['id' => 10, 'user_id' => 2, 'store_id' => 1, 'name' => 'Histórico']);
        (require database_path('migrations/2026_11_03_110000_align_inventory_workflow_columns.php'))->up();
        $this->assertDatabaseHas('inventories', ['id' => 10, 'created_user_id' => 2, 'name' => 'Histórico', 'status' => 'draft']);
    }

    public function test_sessions_require_an_active_warehouse_and_keep_it_immutable(): void
    {
        $inventory = $this->inventory();
        $url = '/test-inventories/'.$inventory->id.'/sessions';
        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $locationId = $this->countingLocation();
        $warehouseId = DB::table('locations')->where('id', $locationId)->value('warehouse_id');
        $session = $this->postJson($url, ['warehouse_id' => $warehouseId])->assertCreated()->assertJsonPath('data.warehouse_id', $warehouseId)->json('data.id');
        $this->putJson($url.'/'.$session, ['warehouse_id' => 999, 'status' => 'open'])->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->putJson($url.'/'.$session, ['status' => 'closed'])->assertOk()->assertJsonPath('data.warehouse_id', $warehouseId);
        DB::table('warehouses')->where('id', $warehouseId)->update(['is_active' => false]);
        $this->postJson($url, ['warehouse_id' => $warehouseId])->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
    }
}
