<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\{InventoryLocationProgressDashboardController, InventoryCountDashboardController, InventoryAdjustmentDashboardController, InventoryWorkspaceDashboardController};
use App\Models\{Inventory, InventorySession, InventoryCount, InventoryAdjustment, Location, Warehouse, Branch, Product, Variant, Store, User, Kardex};
use App\Services\Inventory\InventoryWorkspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Route, Schema};
use Tests\TestCase;

class InventoryWorkspaceTest extends TestCase
{
    private Warehouse $warehouse;
    private Location $leaf;
    private Variant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'workspace_test', 'database.connections.workspace_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        Schema::create('stores', fn (Blueprint $table) => $table->id());
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('addresses', fn (Blueprint $table) => $table->id());
        Schema::create('products', function (Blueprint $table) { $table->id(); $table->integer('store_id'); $table->string('name'); $table->timestamps(); });
        Schema::create('variants', function (Blueprint $table) { $table->id(); $table->integer('product_id'); $table->string('sku'); $table->integer('stock'); $table->integer('status')->default(1); $table->timestamps(); });
        DB::table('stores')->insert([['id' => 1], ['id' => 2]]);
        DB::table('users')->insert([['id' => 1, 'name' => 'Actual'], ['id' => 2, 'name' => 'Otro']]);
        foreach (['2026_04_10_140559_create_inventories_table.php', '2026_11_03_092614_create_inventory_sessions_table.php', '2026_11_03_094309_create_branches_table.php', '2026_11_03_095627_create_warehouses_table.php', '2026_11_03_100000_create_locations_table.php', '2026_11_03_120000_create_inventory_location_progress_table.php', '2026_11_03_120100_create_inventory_counts_table.php', '2026_11_04_150000_unique_inventory_counts_per_session_location_variant.php', '2026_11_03_120200_create_inventory_adjustments_table.php', '2026_02_11_140029_create_kardexes_table.php', '2026_11_04_160000_add_location_to_inventory_sessions.php', '2026_11_04_170000_fix_warehouse_on_inventory_sessions.php', '2026_11_04_180000_allow_multiple_inventory_count_captures.php'] as $file) (require database_path('migrations/'.$file))->up();
        $branch = Branch::create(['store_id' => 1, 'name' => 'Central', 'code' => 'C']);
        $this->warehouse = Warehouse::create(['store_id' => 1, 'branch_id' => $branch->id, 'name' => 'Almacén', 'code' => 'A']);
        $this->leaf = Location::create(['store_id' => 1, 'warehouse_id' => $this->warehouse->id, 'name' => 'Estante']);
        $product = Product::create(['store_id' => 1, 'name' => 'Producto']);
        $this->variant = Variant::create(['product_id' => $product->id, 'sku' => 'SKU-1', 'stock' => 10, 'status' => 1]);
        $this->actingAs(User::findOrFail(1));
        $workspace = fn () => app(InventoryWorkspace::class);
        foreach (['progress' => InventoryLocationProgressDashboardController::class, 'adjustments' => InventoryAdjustmentDashboardController::class] as $slug => $controller) {
            Route::get('/test/{inventory_id}/'.$slug, fn (string $inventory_id) => app($controller)->index(Store::findOrFail(1), $inventory_id));
            Route::post('/test/{inventory_id}/'.$slug, fn (Request $request, string $inventory_id) => app($controller)->store(Store::findOrFail(1), $request, $inventory_id, $workspace()));
            Route::put('/test/{inventory_id}/'.$slug.'/{id}', fn (Request $request, string $inventory_id, string $id) => app($controller)->update(Store::findOrFail(1), $request, $inventory_id, $id, $workspace()));
        }
        Route::get('/test/{inventory_id}/options', fn (string $inventory_id) => app(InventoryWorkspaceDashboardController::class)->options(Store::findOrFail(1), $inventory_id, $workspace()));
        Route::get('/test/{inventory_id}/variants', fn (Request $request, string $inventory_id) => app(InventoryWorkspaceDashboardController::class)->variants(Store::findOrFail(1), $request, $inventory_id));
        Route::get('/test/{inventory_id}/sessions/{session_id}/counts', fn (Request $request, string $inventory_id, string $session_id) => app(InventoryCountDashboardController::class)->index(Store::findOrFail(1), $request, $inventory_id, $session_id));
        Route::post('/test/{inventory_id}/sessions/{session_id}/counts', fn (Request $request, string $inventory_id, string $session_id) => app(InventoryCountDashboardController::class)->store(Store::findOrFail(1), $request, $inventory_id, $session_id, $workspace()));
        Route::put('/test/{inventory_id}/sessions/{session_id}/counts/{count_id}', fn (Request $request, string $inventory_id, string $session_id, string $count_id) => app(InventoryCountDashboardController::class)->update(Store::findOrFail(1), $request, $inventory_id, $session_id, $count_id, $workspace()));
        Route::post('/test/{inventory_id}/adjustments/{id}/{action}', fn (Request $request, string $inventory_id, string $id, string $action) => app(InventoryAdjustmentDashboardController::class)->transition(Store::findOrFail(1), $request, $inventory_id, $id, $action, $workspace()));
    }

    private function inventory(int $store = 1): Inventory
    {
        return Inventory::create(['store_id' => $store, 'created_user_id' => 1, 'name' => 'General', 'status' => 'in_progress']);
    }

    private function countingSession(Inventory $inventory, ?int $locationId = null): InventorySession
    {
        return $inventory->sessions()->create(['warehouse_id' => Location::findOrFail($locationId ?? $this->leaf->id)->warehouse_id, 'user_id' => 1, 'status' => 'open', 'started_at' => now()]);
    }

    private function recordCount(Inventory $inventory, InventorySession $session, int $quantity, array $extra = []): int
    {
        return $this->postJson('/test/'.$inventory->id.'/sessions/'.$session->id.'/counts', [...['location_id' => $this->leaf->id, 'variant_id' => $this->variant->id, 'quantity' => $quantity, 'mode' => 'accumulate'], ...$extra])->assertCreated()->json('data.id');
    }

    private function ready(Inventory $inventory): void
    {
        $inventory->sessions()->update(['status' => 'closed', 'ended_at' => now()]);
        $inventory->locationProgress()->update(['status' => 'verified', 'counted_at' => now(), 'verified_at' => now(), 'verified_by' => 1]);
    }

    public function test_only_leaf_locations_are_registered_once_and_verification_is_sequential(): void
    {
        $inventory = $this->inventory();
        $child = Location::create(['store_id' => 1, 'warehouse_id' => $this->warehouse->id, 'parent_id' => $this->leaf->id, 'name' => 'Nivel']);
        $this->postJson('/test/'.$inventory->id.'/progress', ['location_id' => $this->leaf->id])->assertUnprocessable();
        $id = $this->postJson('/test/'.$inventory->id.'/progress', ['location_id' => $child->id])->assertCreated()->json('data.id');
        $this->postJson('/test/'.$inventory->id.'/progress', ['location_id' => $child->id])->assertUnprocessable();
        $this->putJson('/test/'.$inventory->id.'/progress/'.$id, ['status' => 'verified'])->assertUnprocessable();
        foreach (['in_progress','counted','verified'] as $status) $this->putJson('/test/'.$inventory->id.'/progress/'.$id, ['status' => $status])->assertOk()->assertJsonPath('data.status', $status);
        $this->assertSame(1, $inventory->locationProgress()->first()->verified_by);
        $this->getJson('/test/'.$inventory->id.'/options')->assertOk()->assertJsonCount(1, 'data.locations')->assertJsonPath('data.locations.0.id', $child->id);
    }

    public function test_counts_are_corrected_in_place_and_recounts_use_another_session(): void
    {
        $inventory = $this->inventory(); $session = $this->countingSession($inventory);
        $one = $this->recordCount($inventory, $session, 3, ['user_id' => 2]);
        $url = '/test/'.$inventory->id.'/sessions/'.$session->id.'/counts';
        $this->postJson($url, ['location_id' => $this->leaf->id, 'variant_id' => $this->variant->id, 'quantity' => 4, 'mode' => 'accumulate'])
            ->assertCreated()->assertJsonPath('data.quantity', 4);
        $this->putJson($url.'/'.$one, ['quantity' => 7, 'status' => 'valid'])->assertOk()->assertJsonPath('data.quantity', 7);
        $other = Location::create(['store_id' => 1, 'warehouse_id' => $this->warehouse->id, 'name' => 'Otro estante']);
        $third = $this->recordCount($inventory, $this->countingSession($inventory, $other->id), 2, ['location_id' => $other->id]);
        $nextSession = $this->countingSession($inventory);
        $new = $this->recordCount($inventory, $nextSession, 5, ['mode' => 'recount']);
        $this->assertSame('replaced', InventoryCount::find($one)->status);
        $this->assertSame('valid', InventoryCount::find($third)->status);
        $this->assertSame(2, InventoryCount::find($new)->count_round);
        $this->assertSame(1, InventoryCount::find($one)->user_id);
        $this->assertSame(10, $this->variant->fresh()->stock);
        $this->postJson('/test/'.$inventory->id.'/adjustments', ['variant_id' => $this->variant->id, 'counted_quantity' => 999])
            ->assertCreated()->assertJsonPath('data.counted_quantity', 7)->assertJsonPath('data.system_quantity', 10)->assertJsonPath('data.difference', -3);
    }

    public function test_counts_reject_closed_sessions_foreign_scopes_and_invalid_quantities(): void
    {
        $inventory = $this->inventory(); $session = $this->countingSession($inventory);
        $payload = ['location_id' => $this->leaf->id, 'variant_id' => $this->variant->id, 'quantity' => -1, 'mode' => 'accumulate'];
        $url = '/test/'.$inventory->id.'/sessions/'.$session->id.'/counts';
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson($url, [...$payload, 'quantity' => 1.5])->assertUnprocessable();
        $this->postJson($url, [...$payload, 'quantity' => 1, 'mode' => 'recount'])->assertUnprocessable();
        $this->recordCount($inventory, $session, 0);
        $session->update(['status' => 'closed']);
        $this->postJson($url, [...$payload, 'quantity' => 1])->assertUnprocessable();
        $foreign = $this->inventory(2);
        $this->getJson('/test/'.$foreign->id.'/progress')->assertNotFound();
        $other = $this->countingSession($this->inventory());
        $this->postJson('/test/'.$inventory->id.'/sessions/'.$other->id.'/counts', [...$payload, 'quantity' => 1])->assertNotFound();
    }

    public function test_adjustments_require_review_and_apply_once_for_positive_zero_and_negative_differences(): void
    {
        foreach ([14, 10, 3] as $quantity) {
            $this->variant->refresh()->update(['stock' => 10]);
            $inventory = $this->inventory(); $session = $this->countingSession($inventory);
            $this->recordCount($inventory, $session, $quantity);
            $id = $this->postJson('/test/'.$inventory->id.'/adjustments', ['variant_id' => $this->variant->id])->assertCreated()->json('data.id');
            $base = '/test/'.$inventory->id.'/adjustments/'.$id;
            $this->postJson($base.'/apply')->assertUnprocessable();
            $this->postJson($base.'/approve')->assertUnprocessable();
            $this->ready($inventory);
            $this->postJson($base.'/approve')->assertOk()->assertJsonPath('data.status', 'approved');
            $this->postJson($base.'/apply')->assertOk()->assertJsonPath('data.status', 'applied');
            $this->postJson($base.'/apply')->assertOk();
            $this->assertSame($quantity, $this->variant->fresh()->stock);
            $adjustment = InventoryAdjustment::findOrFail($id);
            $this->assertSame($quantity === 10 ? 0 : 1, $adjustment->kardexes()->count());
            if ($quantity !== 10) { $movement = $adjustment->kardexes()->first(); $this->assertSame(abs($quantity - 10), $movement->quantity); $this->assertSame($quantity > 10 ? 'in' : 'out', $movement->direction); }
        }
    }

    public function test_stale_stock_is_rejected_and_recalculation_removes_approval(): void
    {
        $inventory = $this->inventory(); $session = $this->countingSession($inventory);
        $this->recordCount($inventory, $session, 12);
        $id = $this->postJson('/test/'.$inventory->id.'/adjustments', ['variant_id' => $this->variant->id])->assertCreated()->json('data.id');
        $this->ready($inventory);
        $base = '/test/'.$inventory->id.'/adjustments/'.$id;
        $this->postJson($base.'/approve')->assertOk();
        $this->variant->update(['stock' => 11]);
        $this->postJson($base.'/apply')->assertUnprocessable();
        $this->assertSame(0, Kardex::count());
        $this->putJson($base, ['refresh' => true])->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.system_quantity', 11)->assertJsonPath('data.approved_by', null);
        $this->postJson($base.'/approve')->assertOk();
        $this->postJson($base.'/apply')->assertOk();
    }

    public function test_only_the_author_corrects_a_valid_count_and_edits_reset_verification(): void
    {
        $inventory = $this->inventory(); $session = $this->countingSession($inventory);
        $id = $this->recordCount($inventory, $session, 4);
        $inventory->locationProgress()->update(['status' => 'verified', 'verified_by' => 1, 'verified_at' => now()]);
        $url = '/test/'.$inventory->id.'/sessions/'.$session->id.'/counts/'.$id;
        $this->actingAs(User::findOrFail(2));
        $this->putJson($url, ['quantity' => 5, 'status' => 'valid'])->assertUnprocessable();
        $this->actingAs(User::findOrFail(1));
        $this->putJson($url, ['quantity' => 5, 'status' => 'valid'])->assertOk();
        $this->assertSame('in_progress', $inventory->locationProgress()->first()->status);
        $this->assertNull($inventory->locationProgress()->first()->verified_at);
    }

    public function test_lookup_supports_numeric_ids_and_sku_without_leaking_other_stores(): void
    {
        $inventory = $this->inventory();
        $this->getJson('/test/'.$inventory->id.'/variants?search='.$this->variant->id)->assertOk()->assertJsonPath('data.0.id', $this->variant->id);
        $this->getJson('/test/'.$inventory->id.'/variants?search=SKU-1')->assertOk()->assertJsonCount(1, 'data');
        $otherProduct = Product::create(['store_id' => 2, 'name' => 'Ajeno']);
        Variant::create(['product_id' => $otherProduct->id, 'sku' => 'SKU-FOREIGN', 'stock' => 0, 'status' => 1]);
        $this->getJson('/test/'.$inventory->id.'/variants?search=FOREIGN')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_partial_inventory_counts_cannot_overwrite_global_stock(): void
    {
        $inventory = $this->inventory();
        $inventory->update(['inventoriable_type' => $this->warehouse->getMorphClass(), 'inventoriable_id' => $this->warehouse->id]);
        $session = $this->countingSession($inventory);
        $this->recordCount($inventory, $session, 2);
        $this->postJson('/test/'.$inventory->id.'/adjustments', ['variant_id' => $this->variant->id])->assertUnprocessable()->assertJsonValidationErrors('inventory_id');
        $this->assertSame(10, $this->variant->fresh()->stock);
    }

    public function test_unverified_locations_block_approval_even_if_they_have_no_counts(): void
    {
        $inventory = $this->inventory(); $session = $this->countingSession($inventory);
        $this->recordCount($inventory, $session, 10);
        $id = $this->postJson('/test/'.$inventory->id.'/adjustments', ['variant_id' => $this->variant->id])->assertCreated()->json('data.id');
        $this->ready($inventory);
        Location::create(['store_id' => 1, 'warehouse_id' => $this->warehouse->id, 'name' => 'Sin revisar']);
        $this->postJson('/test/'.$inventory->id.'/adjustments/'.$id.'/approve')->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame('pending', InventoryAdjustment::findOrFail($id)->status);
    }

    public function test_count_barcode_filter_matches_variant_and_keeps_session_scope(): void
    {
        $inventory = $this->inventory();
        $session = $this->countingSession($inventory);
        $first = $this->recordCount($inventory, $session, 3);
        $other = Variant::create(['product_id' => $this->variant->product_id, 'sku' => 'OTHER', 'stock' => 0, 'status' => 1]);
        $this->recordCount($inventory, $session, 7, ['variant_id' => $other->id]);
        $anotherSession = $this->countingSession($inventory);
        $this->recordCount($inventory, $anotherSession, 99);
        $url = '/test/'.$inventory->id.'/sessions/'.$session->id.'/counts';
        $response = $this->getJson($url.'?variant_id='.$this->variant->id)->assertOk()->assertJsonPath('total', 1);
        $this->assertEqualsCanonicalizing([$first], array_column($response->json('data'), 'id'));
        $this->getJson($url)->assertOk()->assertJsonPath('total', 2);
        $this->getJson($url.'?variant_id='.$this->variant->id.'&location_id='.$this->leaf->id)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $first);
        $this->getJson($url.'?variant_id=999999')->assertOk()->assertJsonPath('total', 0);
        $this->getJson($url.'?variant_id=invalid')->assertUnprocessable();
        $this->getJson($url.'?variant_id=0')->assertUnprocessable();
        $foreign = $this->inventory(2);
        $foreignSession = $this->countingSession($foreign);
        $this->getJson('/test/'.$foreign->id.'/sessions/'.$foreignSession->id.'/counts?variant_id='.$this->variant->id)->assertNotFound();
    }

    public function test_captures_accumulate_and_summary_uses_all_sessions_without_cancelled_or_replaced_counts(): void
    {
        $inventory = $this->inventory(); $session = $this->countingSession($inventory);
        $first = $this->recordCount($inventory, $session, 1);
        $this->recordCount($inventory, $session, 5);
        $url = '/test/'.$inventory->id.'/sessions/'.$session->id.'/counts';
        $query = '?variant_id='.$this->variant->id.'&location_id='.$this->leaf->id;
        $this->getJson($url.$query)->assertOk()->assertJsonPath('variant_counted_quantity', 6)->assertJsonPath('total', 2);
        $otherSession = $this->countingSession($inventory);
        $this->recordCount($inventory, $otherSession, 2);
        $this->getJson($url.$query)->assertOk()->assertJsonPath('variant_counted_quantity', 8);
        $this->putJson($url.'/'.$first, ['quantity' => 1, 'status' => 'cancelled'])->assertOk();
        $this->getJson($url.$query)->assertOk()->assertJsonPath('variant_counted_quantity', 7);
        $this->recordCount($inventory, $session, 4, ['mode' => 'recount']);
        $this->getJson($url.$query)->assertOk()->assertJsonPath('variant_counted_quantity', 4);
        $this->assertSame(10, $this->variant->fresh()->stock);
        $otherInventory = $this->inventory();
        $this->recordCount($otherInventory, $this->countingSession($otherInventory), 99);
        $this->getJson($url.$query)->assertOk()->assertJsonPath('variant_counted_quantity', 4);
    }

    public function test_grouped_counts_keep_all_captures_of_each_variant_together(): void
    {
        $inventory = $this->inventory(); $session = $this->countingSession($inventory);
        for ($i = 0; $i < 23; $i++) $this->recordCount($inventory, $session, 1);
        $url = '/test/'.$inventory->id.'/sessions/'.$session->id.'/counts?grouped=1';
        $this->getJson($url)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('last_page', 1)->assertJsonCount(23, 'data');
        $this->getJson($url.'&location_id=999')->assertOk()->assertJsonPath('total', 0)->assertJsonCount(0, 'data');
    }

    public function test_options_include_empty_warehouses_and_respect_inventory_scope_and_store(): void
    {
        $inventory = $this->inventory();
        $empty = Warehouse::create(['store_id' => 1, 'branch_id' => $this->warehouse->branch_id, 'name' => 'Sin zonas', 'code' => 'EMPTY']);
        $otherBranch = Branch::create(['store_id' => 1, 'name' => 'Otra sucursal', 'code' => 'OTHER']);
        $otherWarehouse = Warehouse::create(['store_id' => 1, 'branch_id' => $otherBranch->id, 'name' => 'Otra tienda', 'code' => 'OTHER']);
        $emptyBranch = Branch::create(['store_id' => 1, 'name' => 'Sin almacenes', 'code' => 'NONE']);
        $foreignBranch = Branch::create(['store_id' => 2, 'name' => 'Ajena', 'code' => 'FOREIGN']);
        Warehouse::create(['store_id' => 2, 'branch_id' => $foreignBranch->id, 'name' => 'Ajeno', 'code' => 'FOREIGN']);
        $inactive = Warehouse::create(['store_id' => 1, 'branch_id' => $this->warehouse->branch_id, 'name' => 'Inactivo', 'code' => 'OFF', 'is_active' => false]);
        $url = '/test/'.$inventory->id.'/options';
        $response = $this->getJson($url)->assertOk();
        $this->assertEqualsCanonicalizing([$this->warehouse->id, $empty->id, $otherWarehouse->id], array_column($response->json('data.warehouses'), 'id'));
        $this->assertContains($emptyBranch->id, array_column($response->json('data.branches'), 'id'));
        $this->assertNotContains($foreignBranch->id, array_column($response->json('data.branches'), 'id'));
        $inventory->update(['inventoriable_type' => Branch::class, 'inventoriable_id' => $this->warehouse->branch_id]);
        $this->assertEqualsCanonicalizing([$this->warehouse->id, $empty->id], array_column($this->getJson($url)->assertOk()->json('data.warehouses'), 'id'));
        $inventory->update(['inventoriable_type' => Warehouse::class, 'inventoriable_id' => $empty->id]);
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'data.warehouses')->assertJsonPath('data.warehouses.0.id', $empty->id)->assertJsonCount(0, 'data.locations');
        $inventory->update(['inventoriable_type' => \App\Models\Location::class, 'inventoriable_id' => $this->leaf->id]);
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'data.warehouses')->assertJsonPath('data.warehouses.0.id', $this->warehouse->id);
    }

    public function test_count_location_is_required_and_must_belong_to_the_session_warehouse(): void
    {
        $inventory = $this->inventory(); $session = $this->countingSession($inventory);
        $other = Location::create(['store_id' => 1, 'warehouse_id' => $this->warehouse->id, 'name' => 'Otra zona']);
        $foreignWarehouse = Warehouse::create(['store_id' => 1, 'branch_id' => $this->warehouse->branch_id, 'name' => 'Otro almacén', 'code' => 'W2']);
        $foreign = Location::create(['store_id' => 1, 'warehouse_id' => $foreignWarehouse->id, 'name' => 'Ajena']);
        $url = '/test/'.$inventory->id.'/sessions/'.$session->id.'/counts';
        $this->postJson($url, ['variant_id' => $this->variant->id, 'quantity' => 4])->assertUnprocessable()->assertJsonValidationErrors('location_id');
        $this->postJson($url, ['location_id' => $foreign->id, 'variant_id' => $this->variant->id, 'quantity' => 4])->assertUnprocessable()->assertJsonValidationErrors('location_id');
        $this->postJson($url, ['location_id' => $other->id, 'variant_id' => $this->variant->id, 'quantity' => 4])->assertCreated()->assertJsonPath('data.location_id', $other->id);
    }

    public function test_session_location_migration_preserves_existing_counts(): void
    {
        $migration = require database_path('migrations/2026_11_04_170000_fix_warehouse_on_inventory_sessions.php');
        $migration->down();
        $inventory = $this->inventory();
        $sessionId = DB::table('inventory_sessions')->insertGetId(['inventory_id' => $inventory->id, 'location_id' => $this->leaf->id, 'user_id' => 1, 'status' => 'open', 'started_at' => now()]);
        $count = InventoryCount::create(['inventory_session_id' => $sessionId, 'location_id' => $this->leaf->id, 'variant_id' => $this->variant->id, 'user_id' => 1, 'quantity' => 19, 'count_round' => 1, 'status' => 'valid', 'counted_at' => now()]);
        $migration->up();
        $this->assertSame($this->warehouse->id, (int) InventorySession::findOrFail($sessionId)->warehouse_id);
        $this->assertSame(19, InventoryCount::findOrFail($count->id)->quantity);
    }

    public function test_session_location_migration_rejects_ambiguous_history_without_changes(): void
    {
        $migration = require database_path('migrations/2026_11_04_170000_fix_warehouse_on_inventory_sessions.php');
        $migration->down();
        $inventory = $this->inventory();
        $sessionId = DB::table('inventory_sessions')->insertGetId(['inventory_id' => $inventory->id, 'location_id' => $this->leaf->id, 'user_id' => 1, 'status' => 'open', 'started_at' => now()]);
        $secondWarehouse = Warehouse::create(['store_id' => 1, 'branch_id' => $this->warehouse->branch_id, 'name' => 'Otro almacén', 'code' => 'W2']);
        $other = Location::create(['store_id' => 1, 'warehouse_id' => $secondWarehouse->id, 'name' => 'Otra zona']);
        foreach ([$this->leaf->id, $other->id] as $locationId) InventoryCount::create(['inventory_session_id' => $sessionId, 'location_id' => $locationId, 'variant_id' => $this->variant->id, 'user_id' => 1, 'quantity' => 3, 'count_round' => 1, 'status' => 'valid', 'counted_at' => now()]);
        try {
            $migration->up();
            $this->fail('Una sesión con varias zonas no debe reasignarse automáticamente.');
        } catch (\RuntimeException $exception) {
            $this->assertFalse(Schema::hasColumn('inventory_sessions', 'warehouse_id'));
            $this->assertSame(2, InventoryCount::where('inventory_session_id', $sessionId)->count());
        }
    }

    public function test_session_list_sums_only_valid_counts_and_keeps_sessions_separate(): void
    {
        Route::get('/test/{inventory_id}/sessions', fn (string $inventory_id) => app(\App\Http\Controllers\Api\Dashboard\InventorySessionDashboardController::class)->index(Store::findOrFail(1), $inventory_id));
        $inventory = $this->inventory();
        $session = $this->countingSession($inventory);
        $this->recordCount($inventory, $session, 19);
        $other = Location::create(['store_id' => 1, 'warehouse_id' => $this->warehouse->id, 'name' => 'Otra zona']);
        $this->recordCount($inventory, $session, 4, ['location_id' => $other->id]);
        foreach (['cancelled', 'replaced'] as $status) {
            $location = Location::create(['store_id' => 1, 'warehouse_id' => $this->warehouse->id, 'name' => $status]);
            $id = $this->recordCount($inventory, $session, 100, ['location_id' => $location->id]);
            InventoryCount::findOrFail($id)->update(['status' => $status]);
        }
        $empty = $this->countingSession($inventory);
        $separate = $this->countingSession($inventory);
        $this->recordCount($inventory, $separate, 7);
        $response = $this->getJson('/test/'.$inventory->id.'/sessions')->assertOk();
        $rows = collect($response->json('data'))->keyBy('id');
        $this->assertSame(23, (int) $rows[$session->id]['counted_quantity']);
        $this->assertSame(0, (int) $rows[$empty->id]['counted_quantity']);
        $this->assertSame(7, (int) $rows[$separate->id]['counted_quantity']);
        $this->assertArrayNotHasKey('counts', $rows[$session->id]);
    }

    public function test_inventory_total_rolls_up_valid_counts_from_all_its_sessions(): void
    {
        Route::get('/test-inventory-totals', fn (Request $request) => app(\App\Http\Controllers\Api\Dashboard\InventoryManagementDashboardController::class)->index(Store::findOrFail(1), $request));
        Route::get('/test-inventory-totals/{id}', fn (string $id) => app(\App\Http\Controllers\Api\Dashboard\InventoryManagementDashboardController::class)->show(Store::findOrFail(1), $id));
        $inventory = $this->inventory();
        $first = $this->countingSession($inventory);
        $second = $this->countingSession($inventory);
        $this->recordCount($inventory, $first, 19);
        $this->recordCount($inventory, $second, 7);
        foreach (['cancelled', 'replaced'] as $status) {
            $location = Location::create(['store_id' => 1, 'warehouse_id' => $this->warehouse->id, 'name' => $status]);
            $id = $this->recordCount($inventory, $first, 100, ['location_id' => $location->id]);
            InventoryCount::findOrFail($id)->update(['status' => $status]);
        }
        $other = $this->inventory();
        $this->recordCount($other, $this->countingSession($other), 5);
        $empty = $this->inventory();
        $foreign = $this->inventory(2);
        $response = $this->getJson('/test-inventory-totals')->assertOk()->assertJsonPath('total', 3);
        $rows = collect($response->json('data'))->keyBy('id');
        $this->assertSame(26, (int) $rows[$inventory->id]['counted_quantity']);
        $this->assertSame(5, (int) $rows[$other->id]['counted_quantity']);
        $this->assertSame(0, (int) $rows[$empty->id]['counted_quantity']);
        $this->assertArrayNotHasKey('sessions', $rows[$inventory->id]);
        $this->getJson('/test-inventory-totals/'.$inventory->id)->assertOk()->assertJsonPath('data.counted_quantity', 26);
        $this->getJson('/test-inventory-totals/'.$foreign->id)->assertNotFound();
    }
}
