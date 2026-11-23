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
            $table->string('name'); $table->decimal('price'); $table->string('origin')->nullable(); $table->timestamps();
        });
        Schema::create('variants', function (Blueprint $table) {
            $table->id(); $table->integer('product_id'); $table->integer('stock'); $table->integer('status')->default(1); $table->string('shopify_variant_id')->nullable();
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
        $this->getJson('/test-catalog')->assertOk()->assertJsonPath('data.0.id', 3)->assertJsonPath('per_page', 50);
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

    public function test_only_confirmed_manual_exports_are_marked_in_lists_and_search(): void
    {
        config(['shopify.store' => 'catalog-test']);
        (require database_path('migrations/2026_11_21_100000_create_shopify_product_exports.php'))->up();
        DB::table('products')->where('id', 1)->update(['origin' => 'gid://shopify/Product/101']);
        DB::table('variants')->where('product_id', 2)->update(['shopify_variant_id' => 'gid://shopify/ProductVariant/202']);
        DB::table('shopify_product_exports')->insert(['id' => 'export-test', 'product_id' => 3, 'store_id' => 1,
            'shop_domain' => 'catalog-test.myshopify.com', 'handle' => 'erp-test', 'status' => 'synced', 'remote_product_id' => 'gid://shopify/Product/303']);
        $response = $this->getJson('/test-catalog')->assertOk();
        foreach ($response->json('data') as $product) $this->assertSame($product['id'] === 3, $product['shopify_linked']);
        $search = app(ProductService::class)->search(Store::findOrFail(1), Request::create('/', 'POST', ['search' => 'Charlie']));
        $this->assertTrue($search->first()->shopify_linked);
        $imported = app(ProductService::class)->search(Store::findOrFail(1), Request::create('/', 'POST', ['search' => 'Bravo']));
        $this->assertFalse($imported->first()->shopify_linked);
        DB::table('shopify_product_exports')->delete();
        $this->getJson('/test-catalog')->assertOk()->assertJsonPath('data.0.shopify_linked', false);
    }

    public function test_unlinked_and_failed_exports_are_not_marked_as_linked(): void
    {
        config(['shopify.store' => 'catalog-test']);
        (require database_path('migrations/2026_11_21_100000_create_shopify_product_exports.php'))->up();
        DB::table('shopify_product_exports')->insert(['id' => 'failed-export', 'product_id' => 3, 'store_id' => 1,
            'shop_domain' => 'catalog-test.myshopify.com', 'handle' => 'erp-failed', 'status' => 'failed']);
        $response = $this->getJson('/test-catalog')->assertOk();
        foreach ($response->json('data') as $product) $this->assertFalse($product['shopify_linked']);
    }

    public function test_search_preserves_sort_and_date_filters_even_without_a_term(): void
    {
        $service = app(ProductService::class);
        $store = Store::findOrFail(1);
        $result = $service->search($store, Request::create('/', 'POST', [
            'search' => 'a', 'sort_by' => 'price', 'sort_direction' => 'asc',
        ]));
        $this->assertSame([2, 3, 1], $result->getCollection()->pluck('id')->all());
        $this->assertSame(50, $result->perPage());
        $result = $service->search($store, Request::create('/', 'POST', [
            'search' => '', 'start_date' => '2026-02-01', 'sort_by' => 'stock_total', 'sort_direction' => 'asc',
        ]));
        $this->assertSame([3, 2], $result->getCollection()->pluck('id')->all());
    }
}
