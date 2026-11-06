<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\OptionDashboardController;
use App\Models\Option;
use App\Models\Product;
use App\Models\Store;
use App\Services\Dashboard\Option\OptionRemovalService;
use App\Services\Dashboard\Option\OptionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OptionRemovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'option_removal', 'database.connections.option_removal' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', fn (Blueprint $table) => $table->id());
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
        });
        foreach ([
            '2025_12_26_171717_create_options_table.php', '2026_11_06_100000_add_archive_to_product_options.php',
            '2025_12_26_171730_create_option_values_table.php', '2025_12_26_171743_create_variants_table.php',
            '2025_12_26_180043_create_variant_option_values_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('stores')->insert([['id' => 1], ['id' => 2]]);
        DB::table('products')->insert([['id' => 1, 'store_id' => 1], ['id' => 2, 'store_id' => 1], ['id' => 3, 'store_id' => 2]]);
        DB::table('options')->insert([
            ['id' => 1, 'product_id' => 1, 'store_id' => 1, 'name' => 'color', 'label' => 'Colores'],
            ['id' => 2, 'product_id' => 1, 'store_id' => 1, 'name' => 'size', 'label' => 'Tallas'],
            ['id' => 3, 'product_id' => 3, 'store_id' => 2, 'name' => 'color', 'label' => 'Colores'],
        ]);
        DB::table('option_values')->insert(['id' => 2, 'option_id' => 2, 'value' => 'M']);
        DB::table('variants')->insert(['id' => 3, 'product_id' => 1, 'sku' => 'SIZE-M', 'price' => 10, 'stock' => 7, 'status' => 1]);
        DB::table('variant_option_values')->insert(['variant_id' => 3, 'option_id' => 2, 'option_value_id' => 2]);
        Route::delete('/test-blocks/{product}/{option}', fn ($product, $option) => app(OptionDashboardController::class)
            ->destroy(Store::findOrFail(1), $product, $option, app(OptionRemovalService::class)));
    }

    private function addColorData(): void
    {
        DB::table('option_values')->insert(['id' => 1, 'option_id' => 1, 'value' => 'Azul']);
        foreach ([1 => 1, 2 => 0] as $id => $status) {
            DB::table('variants')->insert(['id' => $id, 'product_id' => 1, 'sku' => 'BLUE-'.$id, 'price' => 12, 'stock' => 4, 'status' => $status]);
            DB::table('variant_option_values')->insert([
                ['variant_id' => $id, 'option_id' => 1, 'option_value_id' => 1],
                ['variant_id' => $id, 'option_id' => 2, 'option_value_id' => 2],
            ]);
        }
        Schema::create('kardexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('variants')->restrictOnDelete();
        });
        DB::table('kardexes')->insert(['variant_id' => 1]);
    }

    public function test_empty_color_block_is_deleted_without_changing_sizes_or_variants(): void
    {
        $this->deleteJson('/test-blocks/1/1')->assertOk()->assertJsonPath('data.archived', false);
        $this->assertDatabaseMissing('options', ['id' => 1]);
        $this->assertDatabaseHas('options', ['id' => 2]);
        $this->assertDatabaseHas('variants', ['id' => 3, 'stock' => 7, 'status' => 1]);
    }

    public function test_block_with_data_is_archived_preserving_ids_values_stock_and_history(): void
    {
        $this->addColorData();
        $this->deleteJson('/test-blocks/1/1')->assertOk()->assertJsonPath('data.archived', true);
        $this->assertNotNull(Option::findOrFail(1)->archived_at);
        $this->assertDatabaseHas('option_values', ['id' => 1, 'value' => 'Azul']);
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4, 'status' => 3]);
        $this->assertDatabaseHas('variants', ['id' => 2, 'stock' => 4, 'status' => 0]);
        $this->assertDatabaseHas('variants', ['id' => 3, 'stock' => 7, 'status' => 1]);
        $this->assertDatabaseHas('kardexes', ['variant_id' => 1]);
        $this->assertDatabaseCount('variant_option_values', 5);
        $this->assertSame([2], Product::findOrFail(1)->options->pluck('id')->all());
        $this->assertCount(2, Product::findOrFail(1)->allOptions);
    }

    public function test_values_without_variants_are_still_archived_and_cannot_be_modified_as_active(): void
    {
        DB::table('option_values')->insert(['id' => 1, 'option_id' => 1, 'value' => 'Azul']);
        $this->deleteJson('/test-blocks/1/1')->assertOk()->assertJsonPath('data.archived', true);
        $this->assertDatabaseHas('option_values', ['id' => 1]);
        $this->deleteJson('/test-blocks/1/1')->assertNotFound();
        $this->assertFalse(Product::findOrFail(1)->options()->whereKey(1)->exists());
    }

    public function test_readding_colors_creates_a_new_block_and_allows_multiple_archived_blocks(): void
    {
        $this->addColorData();
        $this->deleteJson('/test-blocks/1/1')->assertOk();
        $service = new OptionService;
        $new = $service->store(Store::findOrFail(1), 1, 'color');
        $this->assertNotEquals(1, $new->id);
        $this->assertCount(0, $new->option_values);
        $this->assertDatabaseHas('option_values', ['id' => 1, 'option_id' => 1]);
        DB::table('option_values')->insert(['option_id' => $new->id, 'value' => 'Rojo']);
        $this->deleteJson('/test-blocks/1/'.$new->id)->assertOk()->assertJsonPath('data.archived', true);
        $another = $service->store(Store::findOrFail(1), 1, 'color');
        $this->assertNotEquals($new->id, $another->id);
        $this->assertSame(3, Product::findOrFail(1)->allOptions()->where('name', 'color')->count());
        $this->assertSame(1, Product::findOrFail(1)->options()->where('name', 'color')->count());
    }

    public function test_cannot_remove_options_of_other_products_or_other_stores(): void
    {
        $this->deleteJson('/test-blocks/2/1')->assertNotFound();
        $this->deleteJson('/test-blocks/3/3')->assertNotFound();
        $this->assertNull(Option::find(1)->archived_at);
        $this->assertDatabaseCount('options', 3);
    }

    public function test_sizes_with_inventory_and_sales_are_archived_with_their_variants(): void
    {
        $this->addColorData();
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->json('variants');
        });
        $sales = json_encode([['id' => 1, 'sku' => 'BLUE-1', 'quantity' => 1, 'price' => 12]]);
        DB::table('orders')->insert(['store_id' => 1, 'variants' => $sales]);
        $this->deleteJson('/test-blocks/1/2')->assertOk()->assertJsonPath('data.archived', true);
        $this->assertNotNull(Option::findOrFail(2)->archived_at);
        $this->assertDatabaseHas('option_values', ['id' => 2, 'option_id' => 2, 'value' => 'M']);
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4, 'status' => 3]);
        $this->assertDatabaseHas('variants', ['id' => 3, 'stock' => 7, 'status' => 3]);
        $this->assertDatabaseHas('kardexes', ['variant_id' => 1]);
        $this->assertDatabaseHas('orders', ['variants' => $sales]);
        $this->assertDatabaseHas('options', ['id' => 1, 'archived_at' => null]);
    }

    public function test_an_empty_size_option_can_be_removed(): void
    {
        DB::table('options')->insert(['id' => 4, 'product_id' => 2, 'store_id' => 1, 'name' => 'size', 'label' => 'Tallas']);
        $this->deleteJson('/test-blocks/2/4')->assertOk()->assertJsonPath('data.archived', false);
        $this->assertDatabaseMissing('options', ['id' => 4]);
    }

    public function test_failure_archiving_a_variant_rolls_back_the_block(): void
    {
        $this->addColorData();
        DB::unprepared("CREATE TRIGGER fail_archive BEFORE UPDATE ON variants BEGIN SELECT RAISE(ABORT, 'Simulated failure'); END");
        $this->deleteJson('/test-blocks/1/1')->assertStatus(500);
        $this->assertNull(Option::find(1)->archived_at);
        $this->assertDatabaseHas('variants', ['id' => 1, 'status' => 1, 'stock' => 4]);
        $this->assertDatabaseHas('option_values', ['id' => 1]);
    }

    public function test_historical_records_can_still_resolve_archived_variants_and_their_color(): void
    {
        $this->addColorData();
        $this->deleteJson('/test-blocks/1/1')->assertOk();
        foreach ([
            \App\Models\Kardex::class, \App\Models\AcquireVariant::class, \App\Models\BatchVariant::class,
            \App\Models\BatchItem::class, \App\Models\InventoryItem::class, \App\Models\InventoryVariant::class,
            \App\Models\InventoryCount::class, \App\Models\InventoryAdjustment::class,
            \App\Models\ManufactureVariant::class, \App\Models\ProductionVariant::class,
        ] as $modelClass) {
            $record = (new $modelClass)->forceFill(['variant_id' => 1]);
            $this->assertNotNull($record->variant, $modelClass);
            $this->assertSame(1, $record->variant->id);
            $this->assertNotNull($record->variant->optionValues->firstWhere('value', 'Azul')->option->archived_at);
        }
    }
}
