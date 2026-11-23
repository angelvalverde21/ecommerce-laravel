<?php
namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\CollectionDashboardController;
use App\Models\{Manufacture, Order, PhotoSession, Collection, ProjectProcess, ProjectSale, Store, User};
use App\Services\Collections\CollectionWorkspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Route, Schema};
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CollectionsWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'projects_test', 'database.connections.projects_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        Schema::create('stores', function (Blueprint $t) { $t->id(); $t->string('slug'); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('status')->default(1); $t->timestamps(); });
        Schema::create('store_user', function (Blueprint $t) { $t->integer('store_id'); $t->integer('user_id'); });
        Schema::create('manufactures', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->integer('user_id')->nullable(); $t->string('type'); $t->string('name'); $t->dateTime('manufacture_end')->nullable(); $t->timestamps(); });
        (require database_path('migrations/2026_11_23_170000_add_archived_at_to_manufactures.php'))->up();
        Schema::create('inventory_receptions', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->morphs('source'); $t->timestamps(); });
        Schema::create('photo_sessions', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->integer('created_by')->nullable(); $t->string('name'); $t->string('status'); $t->date('delivery_date')->nullable(); $t->timestamps(); });
        Schema::create('products', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->string('name'); $t->timestamps(); });
        Schema::create('variants', function (Blueprint $t) { $t->id(); $t->integer('product_id'); $t->string('title')->nullable(); $t->string('sku')->nullable(); $t->string('shopify_variant_id')->nullable(); $t->integer('stock')->default(0); $t->integer('status')->default(1); });
        Schema::create('options', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('option_values', function (Blueprint $t) { $t->id(); $t->integer('option_id'); $t->string('value'); });
        Schema::create('variant_option_values', function (Blueprint $t) { $t->id(); $t->integer('variant_id'); $t->integer('option_value_id'); });
        Schema::create('manufacture_variant', function (Blueprint $t) { $t->id(); $t->integer('manufacture_id'); $t->integer('variant_id'); $t->integer('quantity'); $t->timestamps(); });
        Schema::create('kardexes', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->string('kardexable_type'); $t->integer('kardexable_id'); $t->integer('variant_id'); $t->string('direction'); $t->integer('quantity'); });
        Schema::create('orders', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->string('shopify_order_name')->nullable(); $t->string('currency_code')->default('PEN'); $t->string('status')->default('registrado'); $t->json('variants'); $t->timestamps(); });
        Schema::create('production_stages', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('name'); $t->integer('position'); });
        Schema::create('production_stage_progress', function (Blueprint $t) { $t->id(); $t->integer('manufacture_id'); $t->integer('stage_id'); $t->string('status')->default('pending'); $t->integer('revision')->default(0); $t->string('sample_review')->default('pending'); $t->timestamps(); });
        Schema::create('production_stage_events', function (Blueprint $t) { $t->id(); $t->integer('production_stage_id'); $t->text('changes')->nullable(); $t->timestamps(); });
        Schema::create('purchases', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->integer('user_id')->nullable(); $t->integer('supplier_id')->nullable(); $t->morphs('purchaseable'); $t->date('purchase_start')->nullable(); $t->date('purchase_end')->nullable(); $t->integer('production_stage_id')->nullable(); $t->text('observations')->nullable(); $t->timestamps(); });
        Schema::create('purchase_items', function (Blueprint $t) { $t->id(); $t->integer('purchase_id'); $t->integer('unit_id')->nullable(); $t->string('name'); $t->decimal('quantity'); $t->decimal('price'); $t->decimal('subtotal'); $t->timestamps(); });
        Schema::create('suppliers', function (Blueprint $t) { $t->id(); $t->integer('user_id'); });
        Schema::create('units', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('payments', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->morphs('paymentable'); $t->decimal('amount'); $t->string('direction'); $t->string('status'); $t->date('date'); $t->timestamps(); });
        Schema::create('attachments', function (Blueprint $t) { $t->id(); $t->morphs('attachable'); });
        Schema::create('images', function (Blueprint $t) { $t->id(); $t->morphs('imageable'); $t->string('thumbnail')->nullable(); $t->string('medium')->nullable(); $t->string('large')->nullable(); });
        (require database_path('migrations/2026_11_13_100000_create_delivery_tracking_tables.php'))->up();
        (require database_path('migrations/2025_11_03_071340_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_11_22_100000_create_projects_workspace.php'))->up();
        (require database_path('migrations/2026_11_22_230000_make_collections_independent.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['ceo', 'sales'] as $role) Role::create(['name' => $role, 'guard_name' => 'api']);
        DB::table('stores')->insert([['id' => 1, 'slug' => 'sorelle'], ['id' => 2, 'slug' => 'other']]);
        $user = User::create(['name' => 'CEO']); $user->assignRole('ceo'); $user->stores()->attach(1); $this->actingAs($user->refresh(), 'api');
        DB::table('products')->insert(['id' => 1, 'store_id' => 1, 'name' => 'Blusa']);
        DB::table('variants')->insert(['id' => 1, 'product_id' => 1, 'stock' => 4]);
        DB::table('production_stages')->insert(['id' => 1, 'code' => 'sewing', 'name' => 'Costura', 'position' => 1]);
        $controller = fn () => app(CollectionDashboardController::class);
        $store = fn ($id) => Store::findOrFail($id);
        Route::post('/collections-test/{s}', fn (Request $r, $s) => $controller()->store($store($s), $r));
        Route::get('/collections-test/{s}', fn (Request $r, $s) => $controller()->index($store($s), $r));
        Route::get('/collections-test/{s}/options', fn (Request $r, $s) => $controller()->options($store($s), $r));
        Route::get('/collections-test/{s}/{p}', fn (Request $r, $s, $p) => $controller()->show($store($s), $r, $p, app(CollectionWorkspace::class)));
        Route::post('/collections-test/{s}/{p}/collections', fn (Request $r, $s, $p) => $controller()->collection($store($s), $r, $p));
        Route::post('/collections-test/{s}/{p}/processes', fn (Request $r, $s, $p) => $controller()->process($store($s), $r, $p));
        Route::post('/collections-test/{s}/{p}/processes/bulk', fn (Request $r, $s, $p) => $controller()->attachProcesses($store($s), $r, $p));
        Route::put('/collections-test/{s}/{p}/processes/{id}', fn (Request $r, $s, $p, $id) => $controller()->updateProcess($store($s), $r, $p, $id));
        Route::delete('/collections-test/{s}/{p}/processes/{id}', fn (Request $r, $s, $p, $id) => $controller()->detach($store($s), $r, $p, $id, app(CollectionWorkspace::class)));
        Route::post('/collections-test/{s}/{p}/sales', fn (Request $r, $s, $p) => $controller()->sale($store($s), $r, $p, app(CollectionWorkspace::class)));
        Route::post('/collections-test/{s}/{p}/sales/{id}/receipts', fn (Request $r, $s, $p, $id) => $controller()->receipt($store($s), $r, $p, $id, app(CollectionWorkspace::class)));
        Route::post('/projects-purchase-test', fn (Request $r) => responseOk(app(\App\Services\Dashboard\Purchase\PurchaseService::class)->store(Store::findOrFail(1), $r)));
    }
    protected function tearDown(): void
    {
        // These focused SQLite fixtures omit unrelated columns. Do not let Eloquent's
        // process-wide guarded-column cache affect suites that use the full schema.
        (new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'guardableColumns'))->setValue(null, []);
        DB::purge('projects_test'); parent::tearDown();
    }
    private function project(): array
    {
        $id = $this->postJson('/collections-test/1', ['name' => 'Verano'])->assertCreated()->json('data.id');
        return [$id, $id];
    }
    private function source(string $type = 'order', int $store = 1): Manufacture
    {
        $source = Manufacture::create(['store_id' => $store, 'type' => $type, 'name' => 'Lote']);
        DB::table('manufacture_variant')->insert(['manufacture_id' => $source->id, 'variant_id' => 1, 'quantity' => 4]);
        DB::table('kardexes')->insert(['store_id' => $store, 'kardexable_type' => Manufacture::class, 'kardexable_id' => $source->id, 'variant_id' => 1, 'direction' => 'in', 'quantity' => 4]);
        return $source;
    }
    private function attach(int $project, int $collection, Manufacture $source): int
    {
        return $this->postJson("/collections-test/1/$project/processes", ['collection_id' => $collection, 'kind' => $source->type, 'source_id' => $source->id])->assertOk()->json('data.id');
    }
    private function order(int $quantity = 4): Order
    {
        return Order::create(['store_id' => 1, 'status' => 'registrado', 'currency_code' => 'PEN', 'variants' => [['id' => 1, 'name' => 'Blusa', 'quantity' => $quantity, 'price' => 25]]]);
    }
    public function test_creates_an_independent_collection_without_a_budget_and_scopes_access(): void
    {
        [$project, $collection] = $this->project();
        $this->assertDatabaseHas('collections', ['id' => $collection, 'name' => 'Verano']);
        $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonPath('data.summary.recovery_percent', null);
        $this->getJson("/collections-test/2/$project")->assertForbidden();
        $foreign = Collection::create(['store_id' => 2, 'name' => 'Ajeno']);
        $this->getJson('/collections-test/1/'.$foreign->id)->assertNotFound();
        $sales = User::create(['name' => 'Ventas']); $sales->assignRole('sales'); $sales->stores()->attach(1); $this->actingAs($sales, 'api');
        $this->getJson('/collections-test/1')->assertForbidden();
    }
    private function inventoryExportSetup(): array
    {
        config(['shopify.store' => 'test-shop.myshopify.com', 'shopify.token' => 'fake-token', 'shopify.catalog_store_slug' => 'sorelle', 'shopify.catalog_version' => '2026-10', 'cache.default' => 'array', 'tests.inventory_conflict' => false]);
        \Illuminate\Support\Facades\Cache::flush();
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        \Illuminate\Support\Facades\Http::fake(fn ($request) => $this->inventoryShopifyResponse($request));
        [$collection] = $this->project();
        $first = $this->source('production'); $second = $this->source('order');
        $this->attach($collection, $collection, $first); $this->attach($collection, $collection, $second);
        DB::table('manufacture_variant')->where('manufacture_id', $first->id)->update(['quantity' => 9]);
        DB::table('manufacture_variant')->where('manufacture_id', $second->id)->update(['quantity' => 11]);
        $this->source('order'); // Units outside this collection must not be exported.
        DB::table('variants')->where('id', 1)->update(['sku' => 'B-S', 'shopify_variant_id' => 'gid://shopify/ProductVariant/101']);
        return [$collection, '/api/v1/sorelle/dashboard/collections/'.$collection.'/inventory'];
    }
    private function inventoryShopifyResponse($request)
    {
        $http = \Illuminate\Support\Facades\Http::class;
        if (str_contains($request['query'], 'CollectionInventoryAccess')) return $http::response(['data' => [
            'shop' => ['myshopifyDomain' => 'test-shop.myshopify.com'], 'currentAppInstallation' => ['accessScopes' => [['handle' => 'write_inventory']]],
            'location' => ['id' => 'gid://shopify/Location/1', 'name' => 'Principal', 'isActive' => true, 'fulfillsOnlineOrders' => true],
        ]]);
        if (str_contains($request['query'], 'CollectionInventoryLevels')) return $http::response(['data' => ['nodes' => [[
            'id' => 'gid://shopify/ProductVariant/101', 'sku' => 'B-S', 'inventoryItem' => ['id' => 'gid://shopify/InventoryItem/201', 'tracked' => true, 'inventoryLevel' => ['quantities' => [['name' => 'available', 'quantity' => 5]]]],
        ]]]]);
        if (str_contains($request['query'], 'SendCollectionInventory')) {
            if (config('tests.inventory_conflict')) return $http::response(['data' => ['inventorySetQuantities' => ['inventoryAdjustmentGroup' => null, 'userErrors' => [['message' => 'Stock changed since review.']]]]]);
            return $http::response(['data' => ['inventorySetQuantities' => ['inventoryAdjustmentGroup' => ['createdAt' => '2026-11-23T20:00:00Z'], 'userErrors' => []]]]);
        }
        throw new \RuntimeException('Unexpected Shopify request in test.');
    }
    public function test_inventory_export_requires_review_then_sends_planned_percentage_once_without_changing_erp_stock(): void
    {
        [$collection, $endpoint] = $this->inventoryExportSetup();
        $preview = $this->postJson($endpoint.'/preview', ['variant_ids' => [1], 'percentage' => 40])->assertOk()
            ->assertJsonPath('data.items.0.planned_units', 20)->assertJsonPath('data.items.0.export_units', 8)->assertJsonPath('data.items.0.current_units', 5);
        \Illuminate\Support\Facades\Http::assertSentCount(2);
        $token = $preview->json('data.token');
        $this->postJson($endpoint.'/send', ['token' => $token, 'quantity' => 999])->assertOk()->assertJsonPath('data.sent_units', 8);
        $this->postJson($endpoint.'/send', ['token' => $token])->assertOk()->assertJsonPath('data.sent_units', 8);
        \Illuminate\Support\Facades\Http::assertSentCount(3);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r['query'], '@idempotent')
            && data_get($r['variables'], 'input.quantities.0.quantity') === 8 && data_get($r['variables'], 'input.quantities.0.changeFromQuantity') === 5);
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4]);
        $this->assertDatabaseHas('manufacture_variant', ['variant_id' => 1, 'quantity' => 11]);
    }
    public function test_inventory_export_rejects_changed_plans_tampered_tokens_and_cross_collection_replays(): void
    {
        [$collection, $endpoint] = $this->inventoryExportSetup();
        $token = $this->postJson($endpoint.'/preview', ['variant_ids' => [1], 'percentage' => 20])->assertOk()->json('data.token');
        DB::table('manufacture_variant')->where('quantity', 9)->update(['quantity' => 10]);
        $this->postJson($endpoint.'/send', ['token' => $token])->assertConflict();
        $this->postJson($endpoint.'/send', ['token' => $token.'tampered'])->assertUnprocessable();
        [$other] = $this->project();
        $this->postJson('/api/v1/sorelle/dashboard/collections/'.$other.'/inventory/send', ['token' => $token])->assertUnprocessable();
        \Illuminate\Support\Facades\Http::assertSentCount(2);
    }
    public function test_inventory_export_rejects_unlinked_outside_variants_and_invalid_percentages_without_sending(): void
    {
        [$collection, $endpoint] = $this->inventoryExportSetup();
        DB::table('variants')->where('id', 1)->update(['shopify_variant_id' => null]);
        $this->postJson($endpoint.'/preview', ['variant_ids' => [1], 'percentage' => 40])->assertUnprocessable();
        $this->postJson($endpoint.'/preview', ['variant_ids' => [999], 'percentage' => 40])->assertUnprocessable();
        $this->postJson($endpoint.'/preview', ['variant_ids' => [1], 'percentage' => 10])->assertUnprocessable();
        $this->postJson($endpoint.'/preview', ['variant_ids' => [1, 1], 'percentage' => 100])->assertUnprocessable();
        $this->postJson($endpoint.'/send', [])->assertUnprocessable();
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
    public function test_inventory_export_reports_shopify_conflicts_and_allows_retrying_the_same_confirmation(): void
    {
        [$collection, $endpoint] = $this->inventoryExportSetup();
        $token = $this->postJson($endpoint.'/preview', ['variant_ids' => [1], 'percentage' => 100])->assertOk()->json('data.token');
        config(['tests.inventory_conflict' => true]);
        $this->postJson($endpoint.'/send', ['token' => $token])->assertUnprocessable()->assertJsonPath('message', 'Shopify no confirmó el cambio de stock. Stock changed since review.');
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4]);
        config(['tests.inventory_conflict' => false]);
        $this->postJson($endpoint.'/send', ['token' => $token])->assertOk();
    }
    public function test_archive_preserves_inventory_and_history_and_can_be_restored(): void
    {
        $production = $this->source('production');
        $endpoint = '/api/v1/sorelle/dashboard/manufactures/productions';
        $this->getJson($endpoint)->assertOk()->assertJsonPath('data.0.can_delete', false);
        $this->putJson($endpoint.'/'.$production->id.'/archive', ['archived' => true])->assertOk();
        $this->assertNotNull($production->fresh()->archived_at);
        $this->assertDatabaseCount('manufacture_variant', 1); $this->assertDatabaseCount('kardexes', 1);
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4]);
        $this->getJson($endpoint)->assertOk()->assertJsonCount(0, 'data');
        $this->postJson($endpoint.'/search', ['archived' => true])->assertOk()->assertJsonCount(1, 'data');
        $this->postJson($endpoint.'/search', ['search' => 'Lote'])->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson($endpoint.'/'.$production->id)->assertUnprocessable();
        $this->putJson($endpoint.'/'.$production->id.'/archive', ['archived' => false])->assertOk();
        $this->assertNull($production->fresh()->archived_at);
        $this->getJson($endpoint)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.can_delete', false);
    }
    public function test_receptions_prevent_deletion_even_when_net_inventory_is_zero(): void
    {
        $production = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Recibida']);
        DB::table('inventory_receptions')->insert(['store_id' => 1, 'source_type' => $production->getMorphClass(), 'source_id' => $production->id]);
        $endpoint = '/api/v1/sorelle/dashboard/manufactures/productions';
        $this->getJson($endpoint)->assertOk()->assertJsonPath('data.0.can_delete', false);
        $this->deleteJson($endpoint.'/'.$production->id)->assertUnprocessable();
        $this->putJson($endpoint.'/'.$production->id.'/archive', ['archived' => true])->assertOk();
        $this->assertDatabaseCount('inventory_receptions', 1);
    }
    public function test_deletes_a_production_and_its_planned_variants_without_deleting_the_catalog(): void
    {
        $production = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Borrador']);
        DB::table('manufacture_variant')->insert(['manufacture_id' => $production->id, 'variant_id' => 1, 'quantity' => 10]);
        DB::table('production_stage_progress')->insert(['manufacture_id' => $production->id, 'stage_id' => 1]);
        $this->deleteJson('/api/v1/sorelle/dashboard/manufactures/productions/'.$production->id)->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertDatabaseMissing('manufactures', ['id' => $production->id]);
        $this->assertDatabaseCount('manufacture_variant', 0);
        $this->assertDatabaseCount('production_stage_progress', 0);
        $this->assertDatabaseCount('variants', 1);
    }
    public function test_production_deletion_cleans_collection_purchases_after_stock_is_resolved(): void
    {
        $production = $this->source('production');
        $this->deleteJson('/api/v1/sorelle/dashboard/manufactures/productions/'.$production->id)->assertUnprocessable();
        DB::table('kardexes')->delete();
        [$collection] = $this->project();
        $process = $this->attach($collection, $collection, $production);
        DB::table('purchases')->insert(['store_id' => 1, 'purchaseable_type' => (new ProjectProcess)->getMorphClass(), 'purchaseable_id' => $process]);
        $this->deleteJson('/api/v1/sorelle/dashboard/manufactures/productions/'.$production->id)->assertOk();
        $this->assertDatabaseMissing('manufactures', ['id' => $production->id]);
        $this->assertDatabaseCount('collections_processes', 0);
        $this->assertDatabaseCount('purchases', 0);
    }
    public function test_production_deletion_removes_purchases_payments_and_active_stages_but_preserves_shared_catalog_and_other_productions(): void
    {
        $production = $this->source('production'); $other = $this->source('production');
        DB::table('kardexes')->where('kardexable_id', $production->id)->delete();
        DB::table('production_stage_progress')->insert(['id' => 1, 'manufacture_id' => $production->id, 'stage_id' => 1, 'status' => 'in_progress']);
        DB::table('production_stage_events')->insert(['production_stage_id' => 1, 'changes' => '{}']);
        DB::table('purchases')->insert([
            ['id' => 1, 'store_id' => 1, 'purchaseable_type' => $production->getMorphClass(), 'purchaseable_id' => $production->id],
            ['id' => 2, 'store_id' => 1, 'purchaseable_type' => $other->getMorphClass(), 'purchaseable_id' => $other->id],
        ]);
        DB::table('purchase_items')->insert(['purchase_id' => 1, 'name' => 'Tela', 'quantity' => 2, 'price' => 10, 'subtotal' => 20]);
        $tracking = DB::table('delivery_trackings')->insertGetId(['store_id' => 1, 'trackable_type' => (new \App\Models\Purchase)->getMorphClass(), 'trackable_id' => 1, 'expected_on' => '2026-11-25']);
        DB::table('delivery_tracking_events')->insert(['delivery_tracking_id' => $tracking, 'user_id' => 1, 'actor_name' => 'CEO', 'type' => 'created', 'changes' => '{}', 'created_at' => now()]);
        DB::table('payments')->insert(['store_id' => 1, 'paymentable_type' => (new \App\Models\Purchase)->getMorphClass(), 'paymentable_id' => 1, 'amount' => 20, 'direction' => 'out', 'status' => 'paid', 'date' => '2026-11-23']);
        DB::table('attachments')->insert(['attachable_type' => (new \App\Models\ProductionStageProgress)->getMorphClass(), 'attachable_id' => 1]);
        DB::table('images')->insert([
            ['imageable_type' => (new \App\Models\Purchase)->getMorphClass(), 'imageable_id' => 1],
            ['imageable_type' => (new \App\Models\Product)->getMorphClass(), 'imageable_id' => 1],
        ]);
        $this->deleteJson('/api/v1/sorelle/dashboard/manufactures/productions/'.$production->id)->assertOk();
        $this->assertDatabaseMissing('purchases', ['id' => 1]); $this->assertDatabaseHas('purchases', ['id' => 2]);
        $this->assertDatabaseCount('purchase_items', 0); $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('delivery_trackings', 0); $this->assertDatabaseCount('delivery_tracking_events', 0);
        $this->assertDatabaseCount('production_stage_progress', 0); $this->assertDatabaseCount('production_stage_events', 0);
        $this->assertDatabaseCount('attachments', 0); $this->assertDatabaseCount('images', 1);
        $this->assertDatabaseHas('products', ['id' => 1]); $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4]);
        $this->assertDatabaseHas('manufacture_variant', ['manufacture_id' => $other->id, 'variant_id' => 1]);
        $this->assertDatabaseHas('manufactures', ['id' => $other->id]);
    }
    public function test_real_production_delete_api_removes_an_unused_collection_process_and_keeps_the_collection(): void
    {
        [$collection] = $this->project();
        $production = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Produccion de polos']);
        $process = $this->attach($collection, $collection, $production);
        $order = $this->order();
        $sale = ProjectSale::create(['process_id' => $process, 'order_id' => $order->id, 'variant_id' => 1, 'line_key' => 'variant:1', 'quantity' => 1, 'unit_price' => 25, 'created_by' => 1]);
        $sale->receipts()->create(['direction' => 'in', 'amount' => 25, 'date' => '2026-11-23', 'reference' => 'TEST-DELETE', 'created_by' => 1]);
        $this->deleteJson('/api/v1/sorelle/dashboard/manufactures/productions/'.$production->id)
            ->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertDatabaseMissing('manufactures', ['id' => $production->id]);
        $this->assertDatabaseMissing('collections_processes', ['id' => $process]);
        $this->assertDatabaseHas('collections', ['id' => $collection]);
        $this->assertDatabaseCount('collections_sales', 0); $this->assertDatabaseCount('collections_receipts', 0);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
    public function test_production_deletion_rejects_other_stores_orders_and_users_without_permission(): void
    {
        $foreign = $this->source('production', 2); $order = $this->source('order');
        $this->deleteJson('/api/v1/sorelle/dashboard/manufactures/productions/'.$foreign->id)->assertNotFound();
        $this->deleteJson('/api/v1/other/dashboard/manufactures/productions/'.$foreign->id)->assertForbidden();
        $this->deleteJson('/api/v1/sorelle/dashboard/manufactures/productions/'.$order->id)->assertNotFound();
        $sales = User::create(['name' => 'Ventas']); $sales->assignRole('sales'); $sales->stores()->attach(1); $this->actingAs($sales, 'api');
        $this->deleteJson('/api/v1/sorelle/dashboard/manufactures/productions/'.$foreign->id)->assertForbidden();
    }
    public function test_sources_can_be_independent_then_linked_once_without_duplication(): void
    {
        [$project, $collection] = $this->project(); [$otherProject, $otherCollection] = $this->project();
        $source = $this->source(); $process = $this->attach($project, $collection, $source);
        $this->postJson("/collections-test/1/$otherProject/processes", ['collection_id' => $otherCollection, 'kind' => 'order', 'source_id' => $source->id])->assertUnprocessable();
        $this->getJson('/collections-test/1/options?kind=order')->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson("/collections-test/1/$project/processes/$process")->assertOk();
        $this->assertDatabaseHas('manufactures', ['id' => $source->id]);
        $this->attach($otherProject, $otherCollection, $source);
        $foreign = $this->source('order', 2);
        $this->postJson("/collections-test/1/$project/processes", ['collection_id' => $collection, 'kind' => 'order', 'source_id' => $foreign->id])->assertNotFound();
    }
    public function test_bulk_attaches_productions_and_orders_with_shared_details_without_duplicating_sources(): void
    {
        [$collection] = $this->project();
        $production = $this->source('production'); $order = $this->source('order');
        $this->getJson('/collections-test/1/options?kind=manufacture')->assertOk()->assertJsonCount(2, 'data');
        $this->postJson("/collections-test/1/$collection/processes/bulk", [
            'sources' => [['kind' => 'production', 'source_id' => $production->id], ['kind' => 'order', 'source_id' => $order->id]],
            'responsible_id' => 1, 'starts_on' => '2026-11-23', 'due_on' => '2026-11-25', 'notes' => 'Grupo de noviembre',
        ])->assertOk()->assertJsonCount(2, 'data');
        $this->assertDatabaseCount('manufactures', 2);
        $this->assertDatabaseCount('collections_processes', 2);
        $this->assertDatabaseHas('collections_processes', ['source_id' => $order->id, 'responsible_id' => 1, 'notes' => 'Grupo de noviembre']);
        $this->getJson('/collections-test/1/options?kind=manufacture')->assertOk()->assertJsonCount(0, 'data');
    }
    public function test_bulk_attachment_rolls_back_all_links_if_one_source_is_already_linked_or_outside_the_store(): void
    {
        [$collection] = $this->project();
        $first = $this->source('production'); $linked = $this->source('order');
        $this->attach($collection, $collection, $linked);
        $this->postJson("/collections-test/1/$collection/processes/bulk", ['sources' => [
            ['kind' => 'production', 'source_id' => $first->id], ['kind' => 'order', 'source_id' => $linked->id],
        ]])->assertUnprocessable();
        $this->assertDatabaseCount('collections_processes', 1);
        $foreign = $this->source('order', 2);
        $this->postJson("/collections-test/1/$collection/processes/bulk", ['sources' => [
            ['kind' => 'production', 'source_id' => $first->id], ['kind' => 'order', 'source_id' => $foreign->id],
        ]])->assertNotFound();
        $this->assertDatabaseCount('collections_processes', 1);
        $this->postJson("/collections-test/1/$collection/processes/bulk", ['sources' => [
            ['kind' => 'production', 'source_id' => $first->id], ['kind' => 'production', 'source_id' => $first->id],
        ]])->assertUnprocessable();
        $this->postJson("/collections-test/1/$collection/processes/bulk", ['sources' => []])->assertUnprocessable();
        $this->assertDatabaseCount('collections_processes', 1);
    }
    public function test_existing_process_options_include_creation_dates_and_do_not_hide_older_records(): void
    {
        for ($i = 1; $i <= 51; $i++) (new Manufacture)->forceFill(['store_id' => 1, 'type' => 'order', 'name' => 'Orden '.$i, 'created_at' => '2026-11-20 15:00:00'])->save();
        $response = $this->getJson('/collections-test/1/options?kind=order')->assertOk()->assertJsonCount(51, 'data');
        $this->assertStringStartsWith('2026-11-20', $response->json('data.0.created_at'));
        $response->assertJsonPath('data.50.name', 'Orden 1');
        $session = new PhotoSession;
        $session->forceFill(['store_id' => 1, 'name' => 'Sesión de fotos', 'status' => 'planned', 'created_at' => '2026-11-19 15:00:00'])->save();
        $photos = $this->getJson('/collections-test/1/options?kind=photo_session')->assertOk()->assertJsonCount(1, 'data');
        $this->assertStringStartsWith('2026-11-19', $photos->json('data.0.created_at'));
    }
    public function test_creates_productions_sessions_and_other_processes_in_the_collection(): void
    {
        [$project, $collection] = $this->project();
        foreach (['production', 'photo_session', 'other'] as $kind) $this->postJson("/collections-test/1/$project/processes", ['collection_id' => $collection, 'kind' => $kind, 'name' => 'Nuevo '.$kind])->assertOk();
        $this->assertDatabaseCount('manufactures', 1); $this->assertDatabaseCount('photo_sessions', 1);
        $this->assertDatabaseCount('production_stage_progress', 1);
        $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonPath('data.summary.processes', 3);
    }
    public function test_consolidates_purchases_and_paid_cash_once_and_keeps_process_ownership(): void
    {
        [$project, $collection] = $this->project();
        $processId = $this->postJson("/collections-test/1/$project/processes", ['collection_id' => $collection, 'kind' => 'other', 'name' => 'Diseño'])->assertOk()->json('data.id');
        $process = ProjectProcess::findOrFail($processId);
        $purchase = $process->purchases()->create(['store_id' => 1]);
        $purchase->items()->create(['name' => 'Servicio', 'quantity' => 2, 'price' => 50, 'subtotal' => 100]);
        $purchase->payments()->create(['store_id' => 1, 'amount' => 40, 'status' => 'paid', 'direction' => 'out', 'date' => '2026-11-20']);
        $purchase->payments()->create(['store_id' => 1, 'amount' => 60, 'status' => 'pending', 'direction' => 'out', 'date' => '2026-11-20']);
        $response = $this->getJson("/collections-test/1/$project")->assertOk();
        $this->assertEquals(100, $response->json('data.summary.purchases'));
        $this->assertEquals(40, $response->json('data.summary.paid'));
        $this->assertEquals(60, $response->json('data.summary.pending_payment'));
        $this->assertEquals(100, $response->json('data.processes.0.purchases_amount'));
        $this->deleteJson("/collections-test/1/$project/processes/$processId")->assertUnprocessable();
        $this->assertDatabaseCount('purchases', 1);
    }
    public function test_products_only_come_from_linked_productions_and_orders_even_if_legacy_manual_associations_exist(): void
    {
        [$project, $collection] = $this->project();
        $other = $this->postJson('/collections-test/1', ['name' => 'Cápsula'])->assertCreated()->json('data.id');
        DB::table('collections_products')->insert(['collection_id' => $other, 'product_id' => 1]);
        $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonCount(0, 'data.products');
        $this->getJson('/collections-test/1/options?kind=product')->assertUnprocessable();
        $this->attach($project, $collection, $this->source('production'));
        $orderProcess = $this->attach($other, $other, $this->source('order'));
        $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonCount(1, 'data.products');
        $this->getJson("/collections-test/1/$collection")->assertOk()->assertJsonCount(1, 'data.products');
        $this->getJson("/collections-test/1/$other")->assertOk()->assertJsonCount(1, 'data.products');
        $this->deleteJson("/collections-test/1/$other/processes/$orderProcess")->assertOk();
        $this->getJson("/collections-test/1/$other")->assertOk()->assertJsonCount(0, 'data.products');
        $this->getJson("/collections-test/1/999")->assertNotFound();
    }
    public function test_collection_products_include_only_linked_manufacture_variants_with_their_own_quantities(): void
    {
        [$collection] = $this->project();
        $order = $this->source('order'); $production = $this->source('production');
        $this->attach($collection, $collection, $order); $this->attach($collection, $collection, $production);
        DB::table('variants')->where('id', 1)->update(['title' => 'Petite', 'sku' => '1659PET', 'stock' => 99]);
        DB::table('manufacture_variant')->where('manufacture_id', $order->id)->update(['quantity' => 25]);
        DB::table('manufacture_variant')->where('manufacture_id', $production->id)->update(['quantity' => 14]);
        DB::table('options')->insert(['id' => 1, 'name' => 'Talla']);
        DB::table('option_values')->insert(['id' => 1, 'option_id' => 1, 'value' => 'PETITE']);
        DB::table('variant_option_values')->insert(['variant_id' => 1, 'option_value_id' => 1]);
        DB::table('variants')->insert(['id' => 2, 'product_id' => 1, 'title' => 'Regular', 'sku' => '1660REG', 'status' => 0]);
        DB::table('manufacture_variant')->insert(['manufacture_id' => $order->id, 'variant_id' => 2, 'quantity' => 15]);
        DB::table('variants')->insert(['id' => 3, 'product_id' => 1, 'title' => 'Sin vincular']);
        $this->source('order'); // Same product in another manufacture must not add units here.
        $response = $this->getJson("/collections-test/1/$collection")->assertOk()->assertJsonCount(1, 'data.products')
            ->assertJsonCount(3, 'data.products.0.manufacture_variants')
            ->assertJsonPath('data.products.0.manufacture_units', 54)
            ->assertJsonPath('data.products.0.manufacture_variants.0.name', 'PETITE')
            ->assertJsonPath('data.products.0.manufacture_variants.0.sku', '1659PET')
            ->assertJsonPath('data.products.0.manufacture_variants.0.quantity', 25)
            ->assertJsonPath('data.products.0.manufacture_variants.0.source_type', 'order')
            ->assertJsonPath('data.products.0.manufacture_variants.1.quantity', 14)
            ->assertJsonPath('data.products.0.manufacture_variants.1.source_type', 'production')
            ->assertJsonPath('data.products.0.manufacture_variants.2.name', 'Regular');
        DB::table('manufacture_variant')->where('manufacture_id', $production->id)->update(['quantity' => 5]);
        $this->getJson("/collections-test/1/$collection")->assertOk()->assertJsonPath('data.products.0.manufacture_units', 45);
    }
    public function test_process_product_counts_distinct_products_instead_of_variants_or_units(): void
    {
        [$collection] = $this->project();
        $source = $this->source('production');
        $this->attach($collection, $collection, $source);
        $this->attach($collection, $collection, $this->source('order'));
        DB::table('products')->insert(['id' => 2, 'store_id' => 1, 'name' => 'Polo']);
        DB::table('variants')->insert([['id' => 2, 'product_id' => 1], ['id' => 3, 'product_id' => 2]]);
        DB::table('manufacture_variant')->insert([
            ['manufacture_id' => $source->id, 'variant_id' => 2, 'quantity' => 10],
            ['manufacture_id' => $source->id, 'variant_id' => 3, 'quantity' => 5],
        ]);
        $this->postJson("/collections-test/1/$collection/processes", ['kind' => 'other', 'name' => 'Actividad'])->assertOk();
        $this->getJson("/collections-test/1/$collection")->assertOk()
            ->assertJsonPath('data.processes.0.products_count', 2)
            ->assertJsonPath('data.processes.1.products_count', 1)
            ->assertJsonPath('data.processes.2.products_count', 0);
    }
    public function test_sales_cannot_claim_more_than_the_order_line_or_received_units(): void
    {
        [$project, $collection] = $this->project();
        $process = $this->attach($project, $collection, $this->source());
        $order = $this->order(3);
        $payload = ['process_id' => $process, 'order_id' => $order->id, 'line_key' => 'variant:1', 'quantity' => 4];
        $this->postJson("/collections-test/1/$project/sales", $payload)->assertUnprocessable();
        $payload['quantity'] = 2;
        $this->postJson("/collections-test/1/$project/sales", $payload)->assertOk();
        $this->postJson("/collections-test/1/$project/sales", $payload)->assertUnprocessable();
        $second = $this->order(); $payload['order_id'] = $second->id; $payload['quantity'] = 3;
        $this->postJson("/collections-test/1/$project/sales", $payload)->assertUnprocessable();
        $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonPath('data.summary.attributed_units', 2);
        $this->deleteJson("/collections-test/1/$project/processes/$process")->assertUnprocessable();
    }
    public function test_cobros_refunds_references_and_recovery_use_real_cash_and_keep_audit_history(): void
    {
        [$project, $collection] = $this->project(); $source = $this->source();
        $process = $this->attach($project, $collection, $source);
        $source->payments()->create(['store_id' => 1, 'amount' => 100, 'status' => 'paid', 'direction' => 'out', 'date' => '2026-11-20']);
        $sale = $this->postJson("/collections-test/1/$project/sales", ['process_id' => $process, 'order_id' => $this->order()->id, 'line_key' => 'variant:1', 'quantity' => 4])->assertOk()->json('data.id');
        $receipt = ['amount' => 60, 'direction' => 'in', 'date' => '2026-11-20', 'reference' => 'YAPE-1'];
        $this->postJson("/collections-test/1/$project/sales/$sale/receipts", $receipt)->assertOk();
        $this->postJson("/collections-test/1/$project/sales/$sale/receipts", $receipt)->assertUnprocessable();
        $this->postJson("/collections-test/1/$project/sales/$sale/receipts", [...$receipt, 'reference' => 'YAPE-2', 'amount' => 50])->assertUnprocessable();
        $this->postJson("/collections-test/1/$project/sales/$sale/receipts", [...$receipt, 'reference' => 'DEV-1', 'direction' => 'out', 'amount' => 10])->assertOk();
        $result = $this->getJson("/collections-test/1/$project")->assertOk();
        $this->assertEquals(50, $result->json('data.summary.collected'));
        $this->assertEquals(50, $result->json('data.summary.recovery_percent'));
        $this->assertEquals(50, $result->json('data.summary.remaining_recovery'));
        $this->assertEquals(-50, $result->json('data.summary.cash_balance'));
        $this->assertEquals(50, $result->json('data.timeline.0.collected'));
    }
    public function test_cancelled_orders_release_attribution_capacity_and_other_currencies_are_rejected(): void
    {
        [$project, $collection] = $this->project(); $process = $this->attach($project, $collection, $this->source());
        $order = $this->order();
        $payload = ['process_id' => $process, 'order_id' => $order->id, 'line_key' => 'variant:1', 'quantity' => 4];
        $this->postJson("/collections-test/1/$project/sales", $payload)->assertOk();
        $order->update(['status' => 'cancelado']);
        $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonPath('data.summary.attributed_units', 0);
        $next = $this->order();
        $this->postJson("/collections-test/1/$project/sales", [...$payload, 'order_id' => $next->id])->assertOk();
        $usd = $this->order(); $usd->update(['currency_code' => 'USD']);
        $this->postJson("/collections-test/1/$project/sales", [...$payload, 'order_id' => $usd->id])->assertUnprocessable();
    }
    public function test_deadlines_are_optional_and_responsibles_must_belong_to_the_store(): void
    {
        $project = $this->postJson('/collections-test/1', ['name' => 'Fechas', 'ends_on' => '2026-12-31'])->assertCreated()->json('data.id');
        $collection = $project;
        $foreign = User::create(['name' => 'Otra tienda']); $foreign->stores()->attach(2);
        $payload = ['collection_id' => $collection, 'kind' => 'other', 'name' => 'Preparación', 'due_on' => '2026-12-15', 'status' => 'in_progress'];
        $this->postJson("/collections-test/1/$project/processes", [...$payload, 'responsible_id' => $foreign->id])->assertUnprocessable();
        $id = $this->postJson("/collections-test/1/$project/processes", [...$payload, 'responsible_id' => 1])->assertOk()->json('data.id');
        $this->assertDatabaseHas('collections_processes', ['id' => $id, 'status' => 'in_progress', 'due_on' => '2026-12-15', 'responsible_id' => 1]);
        $this->getJson('/collections-test/1/options?kind=responsible')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/collections-test/1', ['name' => 'Fechas inválidas', 'starts_on' => '2026-12-31', 'ends_on' => '2026-12-01'])->assertUnprocessable();
    }
    public function test_processes_move_only_between_collections_in_the_same_store(): void
    {
        [$project, $collection] = $this->project(); [$foreignProject, $foreignCollection] = $this->project();
        Collection::whereKey($foreignCollection)->update(['store_id' => 2]);
        $other = $this->postJson('/collections-test/1', ['name' => 'Segunda'])->assertCreated()->json('data.id');
        $id = $this->attach($project, $collection, $this->source());
        $payload = ['collection_id' => $foreignCollection, 'status' => 'pending'];
        $this->putJson("/collections-test/1/$project/processes/$id", $payload)->assertNotFound();
        $this->putJson("/collections-test/1/$project/processes/$id", [...$payload, 'collection_id' => $other])->assertOk();
        $this->getJson("/collections-test/1/$collection")->assertOk()->assertJsonCount(0, 'data.processes');
        $this->getJson("/collections-test/1/$other")->assertOk()->assertJsonCount(1, 'data.processes');
    }
    public function test_completed_production_stages_are_reported_as_completed(): void
    {
        [$project, $collection] = $this->project(); $source = $this->source('production');
        $this->attach($project, $collection, $source);
        DB::table('production_stage_progress')->insert(['manufacture_id' => $source->id, 'stage_id' => 1, 'status' => 'completed']);
        $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonPath('data.processes.0.status', 'completed')->assertJsonPath('data.summary.completed', 1);
    }
    public function test_imported_order_refunds_reduce_attributed_sales_without_erasing_cash_history(): void
    {
        Schema::create('order_items', function (Blueprint $t) { $t->id(); $t->integer('order_id'); $t->integer('variant_id'); $t->string('name'); $t->integer('quantity'); $t->decimal('line_total'); $t->json('source_payload'); });
        [$project, $collection] = $this->project(); $process = $this->attach($project, $collection, $this->source()); $order = $this->order();
        DB::table('order_items')->insert(['id' => 1, 'order_id' => $order->id, 'variant_id' => 1, 'name' => 'Blusa', 'quantity' => 4, 'line_total' => 100, 'source_payload' => '{"currentQuantity":4}']);
        $sale = $this->postJson("/collections-test/1/$project/sales", ['process_id' => $process, 'order_id' => $order->id, 'line_key' => 'item:1', 'quantity' => 4])->assertOk()->json('data.id');
        $this->postJson("/collections-test/1/$project/sales/$sale/receipts", ['amount' => 100, 'direction' => 'in', 'date' => '2026-11-20', 'reference' => 'COBRO-1'])->assertOk();
        DB::table('order_items')->where('id', 1)->update(['source_payload' => '{"currentQuantity":2}']);
        $result = $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonPath('data.sales.0.source_changed', true);
        $this->assertEquals(50, $result->json('data.summary.sales'));
        $this->assertEquals(100, $result->json('data.summary.collected'));
        $this->assertEquals(2, $result->json('data.summary.attributed_units'));
    }
    public function test_existing_purchase_workflow_accepts_sessions_and_own_processes_and_rejects_source_wrappers(): void
    {
        [$project, $collection] = $this->project();
        DB::table('units')->insert(['id' => 1, 'name' => 'Unidad']);
        foreach (['photo_session', 'other'] as $kind) {
            $process = $this->postJson("/collections-test/1/$project/processes", ['collection_id' => $collection, 'kind' => $kind, 'name' => 'Actividad'])->assertOk()->json('data');
            $payload = ['purchaseable_type' => $kind === 'other' ? 'project_process' : 'photo_session', 'purchaseable_id' => $kind === 'other' ? $process['id'] : $process['source_id'],
                'purchase_items' => [['name' => 'Servicio', 'unit_id' => 1, 'quantity' => 2, 'price' => 30, 'subtotal' => 0]]];
            $this->postJson('/projects-purchase-test', $payload)->assertOk()->assertJsonPath('data.items.0.subtotal', 60);
            if ($kind === 'photo_session') $this->postJson('/projects-purchase-test', [...$payload, 'purchaseable_type' => 'project_process', 'purchaseable_id' => $process['id']])->assertUnprocessable();
        }
        $result = $this->getJson("/collections-test/1/$project")->assertOk()->assertJsonCount(2, 'data.purchases');
        $this->assertEquals(120, $result->json('data.summary.purchases'));
    }
    public function test_migration_removes_projects_and_preserves_collection_ids_processes_and_financial_history(): void
    {
        foreach (['collections_receipts', 'collections_sales', 'collections_products', 'collections_processes', 'collections'] as $table) Schema::drop($table);
        (require database_path('migrations/2026_11_22_100000_create_projects_workspace.php'))->up();
        DB::table('projects')->insert(['id' => 9, 'store_id' => 1, 'created_by' => 1, 'name' => 'Proyecto anterior', 'description' => 'Inversión original', 'status' => 'in_progress', 'starts_on' => '2026-11-01']);
        DB::table('projects_collections')->insert(['id' => 12, 'project_id' => 9, 'name' => 'Colección conservada', 'launch_on' => '2026-12-01']);
        $source = $this->source(); $order = $this->order();
        DB::table('projects_processes')->insert(['id' => 15, 'store_id' => 1, 'collection_id' => 12, 'kind' => 'order', 'source_type' => 'manufacture', 'source_id' => $source->id, 'name' => $source->name]);
        DB::table('projects_sales')->insert(['id' => 16, 'process_id' => 15, 'order_id' => $order->id, 'line_key' => 'variant:1', 'variant_id' => 1, 'quantity' => 2, 'unit_price' => 25]);
        DB::table('projects_receipts')->insert(['id' => 17, 'sale_id' => 16, 'direction' => 'in', 'amount' => 20, 'date' => '2026-11-20', 'reference' => 'COBRO-ORIGINAL']);
        $purchase = $source->purchases()->create(['store_id' => 1]);
        $purchase->items()->create(['name' => 'Servicio', 'quantity' => 1, 'price' => 60, 'subtotal' => 60]);
        $purchase->payments()->create(['store_id' => 1, 'amount' => 20, 'status' => 'paid', 'direction' => 'out', 'date' => '2026-11-20']);
        (require database_path('migrations/2026_11_22_230000_make_collections_independent.php'))->up();
        $this->assertFalse(Schema::hasTable('projects'));
        $this->assertFalse(Schema::hasTable('projects_collections'));
        $this->assertDatabaseHas('collections', ['id' => 12, 'name' => 'Colección conservada', 'store_id' => 1, 'status' => 'in_progress', 'description' => 'Inversión original']);
        $this->assertDatabaseHas('collections_processes', ['id' => 15, 'collection_id' => 12]);
        $this->assertDatabaseHas('collections_sales', ['id' => 16, 'process_id' => 15]);
        $this->assertDatabaseHas('collections_receipts', ['id' => 17, 'sale_id' => 16, 'reference' => 'COBRO-ORIGINAL']);
        $result = $this->getJson('/collections-test/1/12')->assertOk()->assertJsonPath('data.collection.id', 12);
        foreach (['purchases' => 60, 'paid' => 20, 'sales' => 50, 'collected' => 20, 'recovery_percent' => 100] as $key => $amount) $this->assertEquals($amount, $result->json('data.summary.'.$key));
    }
    public function test_each_collection_measures_only_its_own_investment(): void
    {
        [$first] = $this->project(); [$second] = $this->project();
        foreach ([[$first, 100, 40], [$second, 300, 150]] as [$collection, $cost, $paid]) {
            $process = $this->postJson("/collections-test/1/$collection/processes", ['kind' => 'other', 'name' => 'Diseño'])->assertOk()->json('data.id');
            $purchase = ProjectProcess::findOrFail($process)->purchases()->create(['store_id' => 1]);
            $purchase->items()->create(['name' => 'Servicio', 'quantity' => 1, 'price' => $cost, 'subtotal' => $cost]);
            $purchase->payments()->create(['store_id' => 1, 'amount' => $paid, 'status' => 'paid', 'direction' => 'out', 'date' => '2026-11-20']);
        }
        $a = $this->getJson("/collections-test/1/$first")->assertOk()->assertJsonCount(1, 'data.processes');
        $b = $this->getJson("/collections-test/1/$second")->assertOk()->assertJsonCount(1, 'data.processes');
        $this->assertEquals(100, $a->json('data.summary.purchases'));
        $this->assertEquals(40, $a->json('data.summary.paid'));
        $this->assertEquals(300, $b->json('data.summary.purchases'));
        $this->assertEquals(150, $b->json('data.summary.paid'));
    }
    public function test_overview_counts_product_orders_once_and_uses_only_their_collection_lines_and_purchase_cost(): void
    {
        [$collection] = $this->project(); $source = $this->source(); $this->attach($collection, $collection, $source);
        DB::table('products')->insert(['id' => 2, 'store_id' => 1, 'name' => 'Ajeno']);
        DB::table('variants')->insert([['id' => 2, 'product_id' => 2], ['id' => 3, 'product_id' => 1]]);
        $a = $this->order(); $a->update(['variants' => [['id' => 1, 'quantity' => 2, 'price' => 25], ['id' => 3, 'quantity' => 1, 'price' => 20], ['id' => 2, 'quantity' => 1, 'price' => 900]]]);
        $b = $this->order(1); $b->update(['variants' => [['id' => 1, 'quantity' => 1, 'price' => 30]]]);
        $this->order()->update(['status' => 'cancelado']);
        $this->order()->update(['store_id' => 2]);
        $this->order()->update(['currency_code' => 'USD']);
        $this->order()->update(['variants' => [['id' => 2, 'quantity' => 1, 'price' => 1000]]]);
        $purchase = $source->purchases()->create(['store_id' => 1]);
        $purchase->items()->create(['name' => 'Servicio', 'quantity' => 2, 'price' => 100, 'subtotal' => 200]);
        $response = $this->getJson("/collections-test/1/$collection")->assertOk()->assertJsonPath('data.overview.orders_count', 2)->assertJsonPath('data.overview.recovery_status', 'progress');
        foreach (['total_sales' => 100, 'total_spent' => 200, 'remaining_recovery' => 100, 'recovery_percent' => 50] as $field => $amount) $this->assertEquals($amount, $response->json('data.overview.'.$field));
        $this->assertDatabaseCount('collections_sales', 0);
    }
    public function test_overview_uses_normalized_order_lines_and_current_quantities_without_double_counting_snapshots(): void
    {
        Schema::create('order_items', function (Blueprint $t) { $t->id(); $t->integer('order_id'); $t->integer('variant_id'); $t->string('name'); $t->integer('quantity'); $t->decimal('line_total'); $t->json('source_payload'); });
        $order = $this->order();
        DB::table('order_items')->insert([
            ['order_id' => $order->id, 'variant_id' => 1, 'name' => 'Blusa', 'quantity' => 4, 'line_total' => 80, 'source_payload' => '{"currentQuantity":2}'],
            ['order_id' => $order->id, 'variant_id' => 1, 'name' => 'Otra talla', 'quantity' => 1, 'line_total' => 10, 'source_payload' => '{"currentQuantity":1}'],
            ['order_id' => $order->id, 'variant_id' => 999, 'name' => 'Otro producto', 'quantity' => 1, 'line_total' => 500, 'source_payload' => '{}'],
        ]);
        $refunded = $this->order();
        DB::table('order_items')->insert(['order_id' => $refunded->id, 'variant_id' => 1, 'name' => 'Devuelto', 'quantity' => 4, 'line_total' => 100, 'source_payload' => '{"currentQuantity":0}']);
        $overview = app(CollectionWorkspace::class)->overview(1, [1], 10000);
        $this->assertEquals(50, $overview['total_sales']);
        $this->assertSame(1, $overview['orders_count']);
        $this->assertEquals(50, $overview['remaining_recovery']);
    }
    public function test_overview_recovery_colors_at_zero_fifty_and_one_hundred_percent(): void
    {
        foreach ([[0, 'low'], [49.99, 'low'], [50, 'progress'], [100, 'progress'], [100.01, 'recovered']] as [$sales, $status]) {
            Order::query()->delete();
            $this->order(1)->update(['variants' => [['id' => 1, 'quantity' => 1, 'price' => $sales]]]);
            $overview = app(CollectionWorkspace::class)->overview(1, [1], 10000);
            $this->assertSame($status, $overview['recovery_status']);
            $this->assertEquals(max(0, 100 - $sales), $overview['remaining_recovery']);
        }
        $overview = app(CollectionWorkspace::class)->overview(1, [1], 0);
        $this->assertNull($overview['recovery_percent']);
        $this->assertSame('neutral', $overview['recovery_status']);
    }
    public function test_empty_placeholder_orders_table_does_not_block_collection_loading(): void
    {
        [$collection] = $this->project(); $this->attach($collection, $collection, $this->source());
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['store_id', 'status', 'currency_code', 'variants']));
        $result = $this->getJson("/collections-test/1/$collection")->assertOk();
        $this->assertEquals(0, $result->json('data.overview.total_sales'));
        $result->assertJsonPath('data.overview.orders_count', 0);
        $this->getJson('/collections-test/1/options?kind=order_sale')->assertOk()->assertJsonCount(0, 'data');
    }
    public function test_incomplete_order_schema_with_rows_does_not_invent_zero_sales_or_cross_store_totals(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['store_id', 'status', 'currency_code', 'variants']));
        DB::table('orders')->insert(['id' => 1]);
        $overview = app(CollectionWorkspace::class)->overview(1, [1], 10000);
        $this->assertFalse($overview['sales_available']);
        $this->assertNull($overview['total_sales']);
        $this->assertNull($overview['orders_count']);
        $this->assertNull($overview['remaining_recovery']);
        $this->assertEquals(100, $overview['total_spent']);
    }
}
