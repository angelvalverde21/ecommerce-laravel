<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\PriceDashboardController;
use App\Models\Store;
use App\Models\PriceLabel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductPricesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'prices_test', 'database.connections.prices_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', fn (Blueprint $table) => $table->id());
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('store_id');
        });
        (require database_path('migrations/2025_12_19_051311_create_prices_table.php'))->up();
        DB::table('stores')->insert(['id' => 1]);
        DB::table('products')->insert([
            ['id' => 1, 'store_id' => 1], ['id' => 2, 'store_id' => 1], ['id' => 3, 'store_id' => 2],
        ]);
        DB::table('prices')->insert([
            'name' => 'Anterior', 'value' => '6.50', 'priceable_type' => 'App\\Models\\Product', 'priceable_id' => 2,
        ]);
        (require database_path('migrations/2026_11_05_100000_add_quantity_price_total_to_prices_table.php'))->up();
        (require database_path('migrations/2026_11_05_110000_create_price_labels_table.php'))->up();

        Route::get('/test-prices/{product}', fn (Request $request, $product) => app(PriceDashboardController::class)->index(Store::find(1), $product, $request));
        Route::get('/test-price-labels', fn (Request $request) => app(PriceDashboardController::class)->labels(Store::find(1), $request));
        Route::post('/test-prices/{product}', fn (Request $request, $product) => app(PriceDashboardController::class)->store(Store::find(1), $product, $request));
        Route::put('/test-prices/{product}/{price}', fn (Request $request, $product, $price) => app(PriceDashboardController::class)->update(Store::find(1), $product, $price, $request));
        Route::delete('/test-prices/{product}/{price}', fn ($product, $price) => app(PriceDashboardController::class)->destroy(Store::find(1), $product, $price));
    }

    public function test_named_prices_are_saved_loaded_updated_and_deleted(): void
    {
        $wholesale = ['name' => 'Precio mayorista', 'quantity' => 3, 'price' => 4, 'total' => 12];
        $id = $this->postJson('/test-prices/1', $wholesale)->assertOk()->json('data.id');
        $this->postJson('/test-prices/1', ['name' => 'Precio normal', 'quantity' => 1, 'price' => 6, 'total' => 6])->assertOk();
        $this->assertDatabaseHas('prices', $wholesale + ['id' => $id]);
        $this->getJson('/test-prices/1')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Precio normal')->assertJsonPath('data.1.total', '12.00');
        $this->putJson('/test-prices/1/'.$id, array_merge($wholesale, ['price' => 5, 'total' => 15]))->assertOk();
        $this->assertDatabaseHas('prices', ['id' => $id, 'quantity' => 3, 'price' => 5, 'total' => 15, 'value' => '5']);
        $this->deleteJson('/test-prices/1/'.$id)->assertOk();
        $this->assertDatabaseMissing('prices', ['id' => $id]);
    }

    public function test_invalid_amounts_and_duplicate_names_are_rejected(): void
    {
        $data = ['name' => 'Mayorista', 'quantity' => 3, 'price' => 4, 'total' => 12];
        foreach ([['quantity' => 0], ['quantity' => -1], ['quantity' => 1.0001], ['price' => -1], ['price' => 4.001], ['total' => 11], ['name' => ' ']] as $invalid) {
            $this->postJson('/test-prices/1', array_merge($data, $invalid))->assertUnprocessable();
        }
        $this->postJson('/test-prices/1', $data)->assertOk();
        $this->postJson('/test-prices/1', $data)->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/test-prices/2', $data)->assertOk();
        $this->postJson('/test-prices/1', ['name' => 'Fraccionado', 'quantity' => 1.005, 'price' => 1, 'total' => 1.01])->assertOk();
    }

    public function test_prices_cannot_be_accessed_through_another_product_or_store(): void
    {
        $data = ['name' => 'Normal', 'quantity' => 1, 'price' => 6, 'total' => 6];
        $id = $this->postJson('/test-prices/1', $data)->assertOk()->json('data.id');
        $this->getJson('/test-prices/3')->assertNotFound();
        $this->postJson('/test-prices/3', $data)->assertNotFound();
        $this->putJson('/test-prices/2/'.$id, $data)->assertNotFound();
        $this->deleteJson('/test-prices/2/'.$id)->assertNotFound();
        $this->assertDatabaseHas('prices', ['id' => $id]);
    }

    public function test_migration_preserves_existing_prices_as_quantity_one(): void
    {
        $this->assertDatabaseHas('prices', ['name' => 'Anterior', 'quantity' => 1, 'price' => 6.5, 'total' => 6.5]);
        $label = PriceLabel::where('code', 'anterior')->firstOrFail();
        $this->assertDatabaseHas('prices', ['name' => 'Anterior', 'price_label_id' => $label->id]);
    }

    public function test_names_have_unique_generated_codes_and_are_reused_across_products(): void
    {
        $data = ['name' => 'precio etiqueta', 'quantity' => 1, 'price' => 6, 'total' => 6];
        $first = $this->postJson('/test-prices/1', $data)->assertOk()
            ->assertJsonPath('data.label.code', 'precio_etiqueta')->json('data');
        $second = $this->postJson('/test-prices/2', array_merge($data, ['name' => ' PRECIO   ETIQUETA ']))
            ->assertOk()->assertJsonPath('data.name', 'precio etiqueta')->json('data');
        $this->assertEquals($first['price_label_id'], $second['price_label_id']);
        $this->assertSame(1, PriceLabel::where('code', 'precio_etiqueta')->count());
        $this->postJson('/test-prices/1', array_merge($data, ['name' => 'Precio Étíqueta']))
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('prices', 3);
    }

    public function test_suggestions_match_inside_the_name_and_filter_prices_by_code(): void
    {
        $data = ['name' => 'precio etiqueta', 'quantity' => 1, 'price' => 6, 'total' => 6];
        $this->postJson('/test-prices/1', $data)->assertOk();
        $this->postJson('/test-prices/1', array_merge($data, ['name' => 'precio mayorista']))->assertOk();
        $this->getJson('/test-price-labels?search=etiqueta')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'precio etiqueta')->assertJsonPath('data.0.code', 'precio_etiqueta');
        $this->getJson('/test-prices/1?label_code=precio_etiqueta')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label.code', 'precio_etiqueta');
        $this->getJson('/test-prices/1?label_code=inexistente')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_invalid_price_does_not_create_a_label_and_editing_keeps_the_label_id(): void
    {
        $data = ['name' => 'Precio nuevo', 'quantity' => 2, 'price' => 6, 'total' => 11];
        $this->postJson('/test-prices/1', $data)->assertUnprocessable();
        $this->assertDatabaseMissing('price_labels', ['code' => 'precio_nuevo']);
        $data['total'] = 12;
        $price = $this->postJson('/test-prices/1', $data)->assertOk()->json('data');
        $this->putJson('/test-prices/1/'.$price['id'], array_merge($data, ['name' => 'PRECIO NUEVO']))
            ->assertOk()->assertJsonPath('data.price_label_id', $price['price_label_id']);
        $this->postJson('/test-prices/1', array_merge($data, ['name' => '!!!']))->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_migration_reuses_one_label_for_existing_name_variations(): void
    {
        $migration = require database_path('migrations/2026_11_05_110000_create_price_labels_table.php');
        $migration->down();
        foreach (['Precio etiqueta', ' PRECIO ETIQUETA '] as $i => $name) {
            DB::table('prices')->insert(['name' => $name, 'value' => '6', 'priceable_type' => 'App\\Models\\Product', 'priceable_id' => $i + 1]);
        }
        $migration->up();
        $label = PriceLabel::where('code', 'precio_etiqueta')->sole();
        $this->assertSame(2, DB::table('prices')->where('price_label_id', $label->id)->count());
    }
}
