<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Models\Product;
use App\Models\Store;
use App\Services\Dashboard\Product\ProductService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductCatalogSortTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'catalog_test', 'database.connections.catalog_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', fn (Blueprint $table) => $table->id());
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->integer('store_id'); $table->integer('category_id')->nullable();
            $table->string('name'); $table->decimal('price'); $table->timestamps();
        });
        Schema::create('variants', function (Blueprint $table) {
            $table->id(); $table->integer('product_id'); $table->integer('stock'); $table->integer('status')->default(1);
        });
        Schema::create('images', function (Blueprint $table) {
            $table->id(); $table->morphs('imageable');
            $table->string('thumbnail'); $table->string('medium'); $table->string('large');
            $table->timestamps();
        });
        Schema::create('variant_option_values', function (Blueprint $table) {
            $table->id(); $table->integer('variant_id'); $table->integer('option_value_id');
        });
        DB::table('stores')->insert(['id' => 1]);
        DB::table('categories')->insert([['id' => 1, 'name' => 'Zeta'], ['id' => 2, 'name' => 'Alpha']]);
        foreach ([
            [1, 1, 1, 'Bravo', 100, '2026-01-01'],
            [2, 1, 2, 'Alpha', 9, '2026-02-01'],
            [3, 1, 2, 'Charlie', 20, '2026-02-01'],
            [4, 2, 1, 'Outside', 999, '2026-03-01'],
        ] as [$id, $store, $category, $name, $price, $date]) {
            DB::table('products')->insert(['id' => $id, 'store_id' => $store, 'category_id' => $category,
                'name' => $name, 'price' => $price, 'created_at' => $date.' 00:00:00']);
        }
        DB::table('variants')->insert([
            ['product_id' => 1, 'stock' => 2], ['product_id' => 1, 'stock' => 3],
            ['product_id' => 2, 'stock' => 10], ['product_id' => 4, 'stock' => 999],
        ]);
        Route::get('/test-catalog', fn (Request $request) => app(ProductDashboardController::class)->index(Store::findOrFail(1), $request));
    }

    public function test_all_columns_sort_both_directions_before_pagination(): void
    {
        foreach ([
            'id' => [1, 2, 3], 'name' => [2, 1, 3], 'category' => [3, 2, 1],
            'price' => [2, 3, 1], 'created_at' => [1, 3, 2], 'stock_total' => [3, 1, 2],
        ] as $field => $ascending) {
            foreach (['asc', 'desc'] as $direction) {
                $expected = $direction === 'asc' ? $ascending : array_reverse($ascending);
                // Equal values always use descending ID as a stable tie breaker.
                if ($direction === 'desc' && $field === 'category') { $expected = [1, 3, 2]; }
                if ($direction === 'desc' && $field === 'created_at') { $expected = [3, 2, 1]; }
                $request = Request::create('/test-catalog', 'GET', ['sort_by' => $field, 'sort_direction' => $direction]);
                $page = app(ProductService::class)->index(Store::findOrFail(1), 2, request: $request);
                $this->assertSame(array_slice($expected, 0, 2), $page->getCollection()->pluck('id')->all(), $field.' '.$direction);
                $this->assertSame(3, $page->total());
                $this->assertStringContainsString('sort_by='.$field, $page->nextPageUrl() ?? '');
            }
        }
    }

    public function test_default_and_legacy_stock_order(): void
    {
        $this->getJson('/test-catalog')->assertOk()->assertJsonPath('data.0.id', 3);
        $this->getJson('/test-catalog?sort=stock')->assertOk()->assertJsonPath('data.0.id', 2);
    }

    public function test_catalog_includes_product_thumbnail_without_searching(): void
    {
        $image = Product::findOrFail(3)->images()->create([
            'thumbnail' => 'products/catalog-thumbnail.jpg',
            'medium' => 'products/catalog-medium.jpg',
            'large' => 'products/catalog-large.jpg',
        ]);

        $this->getJson('/test-catalog')->assertOk()
            ->assertJsonPath('data.0.id', 3)
            ->assertJsonPath('data.0.image.id', $image->id)
            ->assertJsonPath('data.0.image.url_thumbnail', $image->url_thumbnail)
            ->assertJsonPath('data.1.image', null);
    }

    public function test_invalid_sort_parameters_are_rejected(): void
    {
        $this->getJson('/test-catalog?sort_by=invalid')->assertUnprocessable()->assertJsonValidationErrors('sort_by');
        $this->getJson('/test-catalog?sort_direction=invalid')->assertUnprocessable()->assertJsonValidationErrors('sort_direction');
    }

    public function test_search_preserves_sort_and_date_filters_even_without_a_term(): void
    {
        $service = app(ProductService::class);
        $store = Store::findOrFail(1);
        $result = $service->search($store, Request::create('/', 'POST', [
            'search' => 'a', 'sort_by' => 'price', 'sort_direction' => 'asc',
        ]));
        $this->assertSame([2, 3, 1], $result->getCollection()->pluck('id')->all());
        $result = $service->search($store, Request::create('/', 'POST', [
            'search' => '', 'start_date' => '2026-02-01', 'sort_by' => 'stock_total', 'sort_direction' => 'asc',
        ]));
        $this->assertSame([3, 2], $result->getCollection()->pluck('id')->all());
    }
}
