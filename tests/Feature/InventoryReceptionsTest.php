<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\InventoryReceptionDashboardController;
use App\Models\{InventoryReception, Manufacture, Store, User};
use App\Services\Inventory\InventoryReceptionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Route, Schema};
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryReceptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'receipts_test', 'database.connections.receipts_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', function (Blueprint $t) { $t->id(); $t->string('slug'); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('store_user', function (Blueprint $t) { $t->unsignedBigInteger('store_id'); $t->unsignedBigInteger('user_id'); });
        Schema::create('manufactures', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('store_id'); $t->string('type'); $t->timestamps(); });
        Schema::create('products', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('store_id'); $t->string('name'); });
        Schema::create('variants', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('product_id'); $t->string('sku')->nullable(); $t->integer('status')->default(1); $t->integer('stock')->default(0); $t->timestamps(); });
        Schema::create('manufacture_variant', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('manufacture_id'); $t->unsignedBigInteger('variant_id'); });
        Schema::create('options', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('option_values', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('option_id'); $t->string('value'); });
        Schema::create('variant_option_values', function (Blueprint $t) { $t->unsignedBigInteger('variant_id'); $t->unsignedBigInteger('option_value_id'); });
        Schema::create('images', function (Blueprint $t) { $t->id(); $t->morphs('imageable'); });
        Schema::create('kardexes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('store_id'); $t->unsignedBigInteger('product_id'); $t->unsignedBigInteger('variant_id');
            $t->morphs('kardexable'); $t->integer('quantity'); $t->string('direction'); $t->string('comment')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_11_18_120000_create_inventory_receptions.php'))->up();
        (require database_path('migrations/2026_11_18_130000_add_historical_inventory_receptions.php'))->up();
        (require database_path('migrations/2026_11_18_140000_add_inventory_reception_posting.php'))->up();
        (require database_path('migrations/2026_11_18_150000_number_inventory_receptions.php'))->up();
        DB::table('stores')->insert([['id' => 1, 'slug' => 'one'], ['id' => 2, 'slug' => 'two']]);
        DB::table('manufactures')->insert([['id' => 1, 'store_id' => 1, 'type' => 'production'], ['id' => 2, 'store_id' => 2, 'type' => 'production'], ['id' => 3, 'store_id' => 1, 'type' => 'order']]);
        DB::table('products')->insert([['id' => 1, 'store_id' => 1, 'name' => 'Blusa Fresa'], ['id' => 2, 'store_id' => 2, 'name' => 'Otra tienda']]);
        DB::table('variants')->insert([['id' => 1, 'product_id' => 1, 'stock' => 10, 'sku' => 'BLUSA-S'], ['id' => 2, 'product_id' => 1, 'stock' => 5, 'sku' => 'BLUSA-M'], ['id' => 3, 'product_id' => 2, 'stock' => 0, 'sku' => 'OTRA']]);
        DB::table('manufacture_variant')->insert([['manufacture_id' => 1, 'variant_id' => 1], ['manufacture_id' => 1, 'variant_id' => 2]]);
        DB::table('options')->insert(['id' => 1, 'name' => 'size']);
        DB::table('option_values')->insert(['id' => 1, 'option_id' => 1, 'value' => 'S']);
        DB::table('variant_option_values')->insert(['variant_id' => 1, 'option_value_id' => 1]);
        $user = User::create(['name' => 'Responsable']);
        DB::table('store_user')->insert(['store_id' => 1, 'user_id' => $user->id]);
        $this->actingAs($user, 'api');
        Route::post('/receipts-test/{production}', fn (Request $request, $production) => app(InventoryReceptionDashboardController::class)->store(Store::findOrFail(1), $request, $production, app(InventoryReceptionService::class)));
        Route::get('/receipts-test/{production}', fn ($production) => app(InventoryReceptionDashboardController::class)->index(Store::findOrFail(1), $production));
        Route::post('/receipts-test/{production}/{receipt}/reverse', fn (Request $request, $production, $receipt) => app(InventoryReceptionDashboardController::class)->reverse(Store::findOrFail(1), $request, $production, $receipt, app(InventoryReceptionService::class)));
        Route::post('/receipts-test/{production}/{receipt}/post', fn (Request $request, $production, $receipt) => app(InventoryReceptionDashboardController::class)->post(Store::findOrFail(1), $request, $production, $receipt, app(InventoryReceptionService::class)));
        Route::put('/receipts-test/{production}/{receipt}', fn (Request $request, $production, $receipt) => app(InventoryReceptionDashboardController::class)->update(Store::findOrFail(1), $request, $production, $receipt, app(InventoryReceptionService::class)));
        Route::get('/receipts-test/{production}/{receipt}/print', fn ($production, $receipt) => app(InventoryReceptionDashboardController::class)->print(Store::findOrFail(1), $production, $receipt));
    }

    protected function tearDown(): void { DB::purge('receipts_test'); parent::tearDown(); }
    private function draft(array $items = []): array {
        return ['request_id' => (string) Str::uuid(), 'received_on' => now()->toDateString(), 'notes' => 'Entrega parcial', 'items' => $items ?: [['variant_id' => 1, 'quantity' => 3, 'comment' => 'Recepcion']]];
    }
    private function saveAndPost(int $sourceId, array $draft)
    {
        $saved = $this->postJson('/receipts-test/'.$sourceId, $draft)->assertOk();
        return $this->postJson('/receipts-test/'.$sourceId.'/'.$saved->json('data.id').'/post', ['revision' => $saved->json('data.revision')]);
    }

    public function test_confirms_partial_deliveries_with_auditable_snapshot_and_atomic_stock(): void
    {
        $first = $this->saveAndPost(1, $this->draft())->assertOk()->assertJsonPath('data.number', 1)->assertJsonPath('data.received_by', 1)->assertJsonPath('data.items.0.quantity', 3)->assertJsonPath('data.items.0.options.0.value', 'S');
        $this->assertSame(13, DB::table('variants')->where('id', 1)->value('stock'));
        $this->assertSame(1, DB::table('kardexes')->count());
        $this->assertSame(Manufacture::class, DB::table('kardexes')->value('kardexable_type'));
        $this->assertNotNull(DB::table('kardexes')->value('inventory_reception_item_id'));
        $this->saveAndPost(1, $this->draft())->assertOk()->assertJsonPath('data.number', 2);
        $this->assertSame(16, DB::table('variants')->where('id', 1)->value('stock'));
        DB::table('products')->where('id', 1)->update(['name' => 'Nombre cambiado']);
        $this->getJson('/receipts-test/1')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.items.0.product_name', 'Blusa Fresa');
    }

    public function test_retry_never_duplicates_inventory_and_changed_payload_is_rejected(): void
    {
        $draft = $this->draft();
        $id = $this->postJson('/receipts-test/1', $draft)->assertOk()->json('data.id');
        $this->postJson('/receipts-test/1', $draft)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertSame(0, DB::table('kardexes')->count());
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
        $this->postJson("/receipts-test/1/$id/post", ['revision' => 1])->assertOk()->assertJsonPath('data.status', 'posted');
        $this->postJson("/receipts-test/1/$id/post", ['revision' => 1])->assertOk();
        $this->assertSame(1, InventoryReception::count()); $this->assertSame(1, DB::table('kardexes')->count());
        $this->assertSame(13, DB::table('variants')->where('id', 1)->value('stock'));
        $draft['items'][0]['quantity'] = 4;
        $this->postJson('/receipts-test/1', $draft)->assertUnprocessable()->assertJsonValidationErrors('request_id');
    }

    public function test_invalid_item_rolls_back_the_entire_reception(): void
    {
        $this->postJson('/receipts-test/1', $this->draft([['variant_id' => 1, 'quantity' => 3], ['variant_id' => 3, 'quantity' => 1]]))->assertUnprocessable();
        $this->assertSame(0, InventoryReception::count()); $this->assertSame(0, DB::table('kardexes')->count());
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
        DB::table('variants')->where('id', 2)->update(['status' => 0]);
        $this->postJson('/receipts-test/1', $this->draft([['variant_id' => 2, 'quantity' => 1]]))->assertUnprocessable();
        $this->postJson('/receipts-test/1', $this->draft([['variant_id' => 999, 'quantity' => 1]]))->assertUnprocessable();
    }

    public function test_reversal_creates_compensating_movements_once_and_keeps_the_original(): void
    {
        $id = $this->saveAndPost(1, $this->draft())->assertOk()->json('data.id');
        $this->postJson("/receipts-test/1/$id/reverse", ['reason' => 'Cantidad ingresada por error'])->assertOk()->assertJsonPath('data.reversed_by', 1);
        $this->assertSame(2, DB::table('kardexes')->count());
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
        $this->assertSame(1, DB::table('kardexes')->where('direction', 'out')->value('reverses_kardex_id'));
        $this->postJson("/receipts-test/1/$id/reverse", ['reason' => 'Segundo intento'])->assertOk();
        $this->assertSame(2, DB::table('kardexes')->count());
        $this->assertSame('Cantidad ingresada por error', InventoryReception::find($id)->reversal_reason);
        $this->assertSame(1, InventoryReception::count());
    }

    public function test_insufficient_stock_rolls_back_all_reversal_lines(): void
    {
        $id = $this->saveAndPost(1, $this->draft([['variant_id' => 1, 'quantity' => 3], ['variant_id' => 2, 'quantity' => 2]]))->assertOk()->json('data.id');
        DB::table('variants')->where('id', 2)->update(['stock' => 1]);
        $this->postJson("/receipts-test/1/$id/reverse", ['reason' => 'Corrección de recepción'])->assertUnprocessable();
        $this->assertSame(13, DB::table('variants')->where('id', 1)->value('stock'));
        $this->assertSame(2, DB::table('kardexes')->count());
        $this->assertNull(InventoryReception::find($id)->reversed_at);
    }

    public function test_validates_dates_quantities_duplicates_and_tenant_scope(): void
    {
        $draft = $this->draft(); $draft['received_on'] = now()->addDay()->toDateString();
        $this->postJson('/receipts-test/1', $draft)->assertUnprocessable()->assertJsonValidationErrors('received_on');
        foreach ([0, -1, 1.5] as $quantity) $this->postJson('/receipts-test/1', $this->draft([['variant_id' => 1, 'quantity' => $quantity]]))->assertUnprocessable();
        $this->postJson('/receipts-test/1', $this->draft([['variant_id' => 1, 'quantity' => 1], ['variant_id' => 1, 'quantity' => 2]]))->assertUnprocessable();
        $this->postJson('/receipts-test/2', $this->draft())->assertNotFound();
        $this->postJson('/receipts-test/999', $this->draft())->assertNotFound();
        $this->getJson('/receipts-test/2')->assertNotFound();
        $this->assertSame(0, InventoryReception::count());
    }

    public function test_orders_support_the_same_reception_and_reversal_flow(): void
    {
        DB::table('manufacture_variant')->insert(['manufacture_id' => 3, 'variant_id' => 1]);
        $id = $this->saveAndPost(3, $this->draft())->assertOk()->assertJsonPath('data.source_id', 3)->json('data.id');
        $this->getJson('/receipts-test/3')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/receipts-test/1')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("/receipts-test/1/$id/reverse", ['reason' => 'Documento incorrecto'])->assertNotFound();
        $this->postJson("/receipts-test/3/$id/reverse", ['reason' => 'Corrección de orden'])->assertOk();
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
    }

    private function historicalRow(array $changes = []): int
    {
        return DB::table('kardexes')->insertGetId(array_replace([
            'store_id' => 1, 'product_id' => 1, 'variant_id' => 1, 'kardexable_type' => Manufacture::class,
            'kardexable_id' => 1, 'quantity' => 2, 'direction' => 'in', 'comment' => 'Recepcion',
            'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-01 10:00:00',
        ], $changes));
    }

    public function test_overview_and_variant_selection_omit_ledger_rows_but_keep_aggregate_totals(): void
    {
        Schema::table('manufacture_variant', function (Blueprint $table) { $table->integer('quantity')->default(0); $table->decimal('price')->default(0); $table->timestamps(); });
        Schema::create('purchases', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('store_id'); $table->morphs('purchaseable'); });
        Schema::create('purchase_items', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('purchase_id'); $table->decimal('subtotal'); });
        Schema::create('payments', function (Blueprint $table) { $table->id(); $table->morphs('paymentable'); $table->decimal('amount'); });
        Schema::create('delivery_trackings', function (Blueprint $table) { $table->id(); $table->morphs('trackable'); });
        $this->historicalRow(['quantity' => 4]);
        $service = app(\App\Services\Dashboard\Crud\ManufactureService::class);
        $store = Store::findOrFail(1);
        $selection = $service->variants($store, 1, false);
        $this->assertFalse($selection->first()->variant->relationLoaded('manufactureKardexes'));
        $overview = $service->show($store, 1, false);
        $this->assertEquals(4, $overview->sum_kardexes);
        $this->assertFalse($overview->variants->first()->relationLoaded('manufactureKardexes'));
        DB::enableQueryLog(); DB::flushQueryLog();
        $summary = $service->show($store, 1, false, true);
        $this->assertCount(1, DB::getQueryLog()); DB::disableQueryLog();
        $this->assertSame([], $summary->getRelations());
        $this->assertEquals(4, $summary->sum_kardexes);
        $legacy = $service->variants($store, 1);
        $this->assertTrue($legacy->first()->variant->relationLoaded('manufactureKardexes'));
        $this->assertCount(1, $legacy->first()->variant->manufactureKardexes);
    }

    public function test_numbers_are_per_source_persist_after_reversal_and_are_used_in_pdf(): void
    {
        $first = $this->saveAndPost(1, $this->draft())->assertOk();
        $id = $first->json('data.id');
        $this->postJson("/receipts-test/1/$id/reverse", ['reason' => 'Error de recepción'])->assertOk()->assertJsonPath('data.number', 1);
        $next = $this->postJson('/receipts-test/1', $this->draft())->assertOk()->assertJsonPath('data.number', 2);
        $nextId = $next->json('data.id');
        DB::table('manufacture_variant')->insert(['manufacture_id' => 3, 'variant_id' => 1]);
        $order = $this->postJson('/receipts-test/3', $this->draft())->assertOk()->assertJsonPath('data.number', 1);
        $orderId = $order->json('data.id');
        $this->assertNotSame(1, $orderId);
        $this->get("/receipts-test/3/$orderId/print")->assertOk()->assertHeader('content-disposition', 'inline; filename=recepcion-1.pdf');
        $this->putJson("/receipts-test/1/$nextId", [...$this->draft(), 'revision' => 1])->assertOk()->assertJsonPath('data.number', 2);
    }

    public function test_migration_numbers_existing_documents_in_date_order_without_posting_them(): void
    {
        $first = $this->postJson('/receipts-test/1', $this->draft())->assertOk()->json('data.id');
        $second = $this->postJson('/receipts-test/1', $this->draft())->assertOk()->json('data.id');
        DB::table('inventory_receptions')->where('id', $first)->update(['received_on' => '2026-09-02']);
        DB::table('inventory_receptions')->where('id', $second)->update(['received_on' => '2026-09-01']);
        $migration = require database_path('migrations/2026_11_18_150000_number_inventory_receptions.php');
        $migration->down(); $migration->up();
        $this->assertSame(1, InventoryReception::find($second)->number);
        $this->assertSame(2, InventoryReception::find($first)->number);
        $this->assertSame(2, InventoryReception::where('status', 'draft')->count());
        $this->assertSame(0, DB::table('kardexes')->count());
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
    }

    public function test_saved_receipt_can_be_edited_and_printed_without_affecting_inventory(): void
    {
        $draft = $this->draft();
        $saved = $this->postJson('/receipts-test/1', $draft)->assertOk()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.stock_applied', false);
        $id = $saved->json('data.id');
        $draft['items'][0]['quantity'] = 8;
        $this->putJson("/receipts-test/1/$id", [...$draft, 'revision' => 1])->assertOk()->assertJsonPath('data.revision', 2)->assertJsonPath('data.items.0.quantity', 8);
        $this->putJson("/receipts-test/1/$id", [...$draft, 'revision' => 1])->assertUnprocessable()->assertJsonValidationErrors('revision');
        $this->postJson("/receipts-test/1/$id/post", ['revision' => 1])->assertUnprocessable()->assertJsonValidationErrors('revision');
        $this->assertSame(0, DB::table('kardexes')->count());
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
        $pdf = $this->get("/receipts-test/1/$id/print")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertSame('draft', InventoryReception::find($id)->status);
        $this->postJson("/receipts-test/1/$id/reverse", ['reason' => 'No tiene ingreso'])->assertUnprocessable();
        $this->postJson("/receipts-test/1/$id/post", ['revision' => 2])->assertOk()->assertJsonPath('data.status', 'posted')->assertJsonPath('data.posted_by', 1);
        $this->assertSame(18, DB::table('variants')->where('id', 1)->value('stock'));
        $this->putJson("/receipts-test/1/$id", [...$draft, 'revision' => 3])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->get("/receipts-test/2/$id/print")->assertNotFound();
    }

    public function test_post_revalidates_all_saved_variants_and_rolls_back_without_losing_the_draft(): void
    {
        $saved = $this->postJson('/receipts-test/1', $this->draft([['variant_id' => 1, 'quantity' => 2], ['variant_id' => 2, 'quantity' => 3]]))->assertOk();
        $id = $saved->json('data.id');
        DB::table('variants')->where('id', 2)->update(['status' => 0]);
        $this->postJson("/receipts-test/1/$id/post", ['revision' => 1])->assertUnprocessable();
        $this->assertSame(0, DB::table('kardexes')->count());
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
        $this->assertSame(2, InventoryReception::find($id)->items()->count());
        $this->assertSame('draft', InventoryReception::find($id)->status);
        $this->postJson("/receipts-test/2/$id/post", ['revision' => 1])->assertNotFound();
    }

    public function test_historical_import_groups_exact_timestamps_preserves_ledger_and_never_updates_stock(): void
    {
        $first = $this->historicalRow(); $second = $this->historicalRow(['quantity' => 3]);
        $this->historicalRow(['created_at' => '2026-09-01 10:00:01']);
        $out = $this->historicalRow(['direction' => 'out', 'comment' => 'Fallado']);
        $repaired = $this->historicalRow(['comment' => 'Reparado']);
        $importer = app(\App\Services\Inventory\HistoricalReceptionImporter::class);
        $preview = $importer->run(null, true);
        $this->assertSame(2, $preview['receptions']); $this->assertSame(0, InventoryReception::count());
        $before = DB::table('kardexes')->get()->map(fn ($row) => (array) $row)->all();
        $result = $importer->run();
        $this->assertSame(2, $result['receptions']); $this->assertSame(3, $result['movements']); $this->assertSame(7, $result['quantity']);
        $this->assertSame(5, DB::table('kardexes')->count());
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
        foreach ($before as $row) {
            $after = (array) DB::table('kardexes')->find($row['id']);
            unset($after['inventory_reception_item_id'], $row['inventory_reception_item_id']);
            $this->assertSame($row, $after);
        }
        $receipt = InventoryReception::orderBy('id')->first();
        $this->assertTrue($receipt->is_historical); $this->assertFalse($receipt->stock_applied); $this->assertNull($receipt->received_by);
        $this->assertSame('2026-09-01', $receipt->received_on->toDateString());
        $this->assertSame(5, $receipt->items->first()->quantity);
        $this->assertSame(DB::table('kardexes')->where('id', $first)->value('inventory_reception_item_id'), DB::table('kardexes')->where('id', $second)->value('inventory_reception_item_id'));
        $this->assertNull(DB::table('kardexes')->where('id', $out)->value('inventory_reception_item_id'));
        $this->assertNull(DB::table('kardexes')->where('id', $repaired)->value('inventory_reception_item_id'));
        $this->assertSame(0, $importer->run()['receptions']);
        $this->postJson('/receipts-test/1/'.$receipt->id.'/reverse', ['reason' => 'Rectificar recepción antigua'])->assertOk();
        $this->assertSame(10, DB::table('variants')->where('id', 1)->value('stock'));
        $this->assertSame(2, DB::table('kardexes')->whereNotNull('reverses_kardex_id')->count());
    }

    public function test_historical_import_preserves_missing_catalog_identifiers_and_skips_incomplete_groups(): void
    {
        $missing = $this->historicalRow(['variant_id' => 999, 'product_id' => 999]);
        $this->historicalRow(['variant_id' => 0, 'created_at' => '2026-09-02 10:00:00']);
        $result = app(\App\Services\Inventory\HistoricalReceptionImporter::class)->run();
        $this->assertSame(1, $result['receptions']); $this->assertCount(1, $result['skipped']);
        $item = InventoryReception::first()->items->first();
        $this->assertSame(999, $item->variant_id); $this->assertSame('Producto #999', $item->product_name);
        $this->assertNull($item->sku); $this->assertSame([], $item->options);
        $this->assertNotNull(DB::table('kardexes')->where('id', $missing)->value('inventory_reception_item_id'));
        $this->assertSame(2, DB::table('kardexes')->count());
    }
}
