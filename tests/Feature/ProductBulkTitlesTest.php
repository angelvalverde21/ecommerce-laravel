<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductBulkTitlesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'titles_test', 'database.connections.titles_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Http::preventStrayRequests();
        foreach (['users', 'stores', 'categories'] as $table) {
            Schema::create($table, fn (Blueprint $blueprint) => $blueprint->id());
            DB::table($table)->insert(['id' => 1]);
        }
        DB::table('stores')->insert(['id' => 2]);
        (require database_path('migrations/0001_03_01_000200_create_products_table.php'))->up();
        foreach ([1, 2, 3] as $id) {
            Product::create(['name' => 'Producto '.$id, 'slug' => 'producto-'.$id, 'store_id' => $id === 3 ? 2 : 1, 'user_id' => 1, 'price' => 20, 'quantity' => 8]);
        }
        Route::put('/test-bulk-titles', fn (Request $request) => app(ProductDashboardController::class)->updateTitles(Store::findOrFail(1), $request));
    }

    public function test_updates_titles_together_and_preserves_other_fields(): void
    {
        $before = DB::table('products')->get()->keyBy('id');
        $this->putJson('/test-bulk-titles', ['products' => [
            ['id' => 1, 'name' => '  Pantalón Negro  '], ['id' => 2, 'name' => 'Pantalón Beige'],
        ]])->assertOk()->assertJsonPath('data.0.name', 'Pantalón Negro')->assertJsonPath('data.1.name', 'Pantalón Beige');
        foreach ([1, 2] as $id) {
            $after = DB::table('products')->find($id);
            foreach (['slug', 'price', 'quantity', 'status', 'store_id', 'category_id', 'body', 'sku'] as $field) {
                $this->assertEquals($before[$id]->$field, $after->$field, $field);
            }
        }
        $this->assertSame('Producto 3', Product::findOrFail(3)->name);
        Http::assertNothingSent();
    }

    public function test_rejects_foreign_or_missing_products_without_partial_updates(): void
    {
        foreach ([3, 999] as $id) {
            $this->putJson('/test-bulk-titles', ['products' => [
                ['id' => 1, 'name' => 'Nuevo'], ['id' => $id, 'name' => 'Otro'],
            ]])->assertNotFound();
            $this->assertSame('Producto 1', Product::findOrFail(1)->name);
        }
    }

    public function test_validates_entire_batch_before_saving(): void
    {
        foreach ([null, '', '   ', str_repeat('x', 256), ['bad']] as $name) {
            $this->putJson('/test-bulk-titles', ['products' => [
                ['id' => 1, 'name' => 'Nuevo'], ['id' => 2, 'name' => $name],
            ]])->assertUnprocessable();
            $this->assertSame('Producto 1', Product::findOrFail(1)->name);
        }
        $this->putJson('/test-bulk-titles', ['products' => []])->assertUnprocessable();
        $this->putJson('/test-bulk-titles', ['products' => [
            ['id' => 1, 'name' => 'A'], ['id' => 1, 'name' => 'B'],
        ]])->assertUnprocessable();
        $this->putJson('/test-bulk-titles', ['products' => array_map(fn ($id) => ['id' => $id, 'name' => 'A'], range(1, 36))])->assertUnprocessable();
    }

    public function test_bulk_route_is_registered_before_the_product_update_route(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/v1/sorelle/dashboard/products/bulk-titles', 'PUT'));
        $this->assertSame(ProductDashboardController::class.'@updateTitles', $route->getActionName());
        $this->assertContains('auth:api', $route->gatherMiddleware());
    }
}
