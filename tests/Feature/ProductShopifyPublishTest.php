<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\ProductShopifyPublishController;
use App\Models\ShopifyProductExport;
use App\Models\Store;
use App\Models\User;
use App\Services\Shopify\Catalog\CatalogPublisher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductShopifyPublishTest extends TestCase
{
    private ?array $remote = null;
    private int $creates = 0;
    private bool $timeout = false;
    private array $existingSkus = [];
    private bool $deleteTimeout = false;
    private bool $deleteFails = false;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'catalog_test', 'database.connections.catalog_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
            'shopify.store' => 'catalog-test', 'shopify.token' => 'test-token', 'shopify.catalog_version' => '2026-10', 'shopify.catalog_store_slug' => 'sorelle']);
        Http::preventStrayRequests();
        Schema::create('stores', function (Blueprint $t) { $t->id(); $t->string('slug'); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('store_user', function (Blueprint $t) { $t->integer('store_id'); $t->integer('user_id'); });
        Schema::create('products', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->string('name'); $t->integer('status')->default(1); $t->string('origin')->nullable(); $t->timestamps(); });
        Schema::create('variants', function (Blueprint $t) { $t->id(); $t->integer('product_id'); $t->string('sku'); $t->integer('status')->default(1); $t->integer('stock')->default(0); $t->string('shopify_variant_id')->nullable(); $t->timestamps(); });
        Schema::create('options', function (Blueprint $t) { $t->id(); $t->integer('product_id'); $t->string('name'); $t->string('label'); $t->integer('sort_order')->default(0); $t->timestamp('archived_at')->nullable(); });
        Schema::create('option_values', function (Blueprint $t) { $t->id(); $t->integer('option_id'); $t->string('value'); });
        Schema::create('variant_option_values', function (Blueprint $t) { $t->id(); $t->integer('variant_id'); $t->integer('option_id'); $t->integer('option_value_id'); });
        (require database_path('migrations/2025_11_03_071340_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_11_21_100000_create_shopify_product_exports.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['ceo', 'sales'] as $role) Role::create(['name' => $role, 'guard_name' => 'api']);
        DB::table('stores')->insert([['id' => 1, 'slug' => 'sorelle'], ['id' => 2, 'slug' => 'other']]);
        $user = User::create(['name' => 'CEO']); $user->assignRole('ceo'); $user->stores()->attach(1); $this->actingAs($user, 'api');
        DB::table('products')->insert([['id' => 1, 'store_id' => 1, 'name' => 'Blusa nueva'], ['id' => 2, 'store_id' => 2, 'name' => 'Ajeno']]);
        DB::table('options')->insert(['id' => 1, 'product_id' => 1, 'name' => 'size', 'label' => 'Talla']);
        foreach (['S', 'M'] as $index => $size) {
            $id = $index + 1;
            DB::table('variants')->insert(['id' => $id, 'product_id' => 1, 'sku' => 'BL-'.$size, 'stock' => 12]);
            DB::table('option_values')->insert(['id' => $id, 'option_id' => 1, 'value' => $size]);
            DB::table('variant_option_values')->insert(['variant_id' => $id, 'option_id' => 1, 'option_value_id' => $id]);
        }
        Route::post('/catalog-test/preview', fn (Request $r) => app(ProductShopifyPublishController::class)->preview(Store::findOrFail(1), $r, app(CatalogPublisher::class)));
        Route::post('/catalog-test/{id}/publish', fn (Request $r, $id) => app(ProductShopifyPublishController::class)->publish(Store::findOrFail(1), $r, (int) $id, app(CatalogPublisher::class)));
        Route::post('/catalog-test/{id}/reset', fn (Request $r, $id) => app(ProductShopifyPublishController::class)->reset(Store::findOrFail(1), $r, (int) $id, app(CatalogPublisher::class)));
        Http::fake(function ($request) {
            $query = $request['query'];
            if (str_contains($query, 'ResetErpProduct')) return Http::response(['data' => ['product' => $this->remote]]);
            if (str_contains($query, 'DeleteErpProduct')) {
                if ($this->deleteFails) return Http::response(['data' => ['productDelete' => ['deletedProductId' => null, 'userErrors' => [['message' => 'Denied']]]]]);
                $id = $this->remote['id']; $this->remote = null;
                if ($this->deleteTimeout) throw new ConnectionException('simulated deletion timeout');
                return Http::response(['data' => ['productDelete' => ['deletedProductId' => $id, 'userErrors' => []]]]);
            }
            if (str_contains($query, 'CatalogAccess')) return Http::response(['data' => ['shop' => ['myshopifyDomain' => 'catalog-test.myshopify.com'], 'currentAppInstallation' => ['accessScopes' => [['handle' => 'write_products']]]]]);
            if (str_contains($query, 'FindErpProduct')) return Http::response(['data' => ['productByIdentifier' => $this->remote]]);
            if (str_contains($query, 'CatalogSkuCheck')) return Http::response(['data' => ['productVariants' => ['nodes' => array_map(fn ($sku) => ['sku' => $sku], $this->existingSkus), 'pageInfo' => ['hasNextPage' => false]]]]);
            if (str_contains($query, 'PublishErpProduct')) {
                $this->creates++;
                $input = data_get($request->data(), 'variables.input');
                $this->remote = ['id' => 'gid://shopify/Product/900', 'handle' => $input['handle'], 'status' => $input['status'], 'metafield' => ['value' => $input['metafields'][0]['value']],
                    'variants' => ['nodes' => array_map(fn ($variant, $index) => ['id' => 'gid://shopify/ProductVariant/'.(1000 + $index), 'sku' => $variant['sku'],
                        'selectedOptions' => array_map(fn ($value) => ['name' => $value['optionName'], 'value' => $value['name']], $variant['optionValues'])], $input['variants'], array_keys($input['variants'])), 'pageInfo' => ['hasNextPage' => false]]];
                if ($this->timeout) throw new ConnectionException('simulated timeout');
                return Http::response(['data' => ['productSet' => ['product' => $this->remote, 'userErrors' => []]]]);
            }
            throw new \RuntimeException('Unexpected external request.');
        });
    }

    protected function tearDown(): void { DB::purge('catalog_test'); parent::tearDown(); }
    private function token(): string { return $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT'])->assertOk()->json('data.items.0.token'); }

    private function resetToken(): string
    {
        return $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT', 'mode' => 'reset'])->assertOk()->json('data.items.0.token');
    }

    public function test_reset_deletes_export_and_links_preserves_local_product_and_allows_reexport(): void
    {
        $this->postJson('/catalog-test/1/publish', ['token' => $this->token()])->assertOk();
        $token = $this->resetToken();
        $this->postJson('/catalog-test/1/reset', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'reset');
        $this->assertNull($this->remote);
        $this->assertDatabaseCount('shopify_product_exports', 0);
        $this->assertDatabaseHas('products', ['id' => 1, 'name' => 'Blusa nueva']);
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 12, 'shopify_variant_id' => null]);
        $this->postJson('/catalog-test/1/reset', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'reset');
        $this->postJson('/catalog-test/1/publish', ['token' => $this->token()])->assertOk()->assertJsonPath('data.status', 'synced');
        $this->postJson('/catalog-test/1/reset', ['token' => $token])->assertConflict();
    }

    public function test_failed_or_ambiguous_deletion_keeps_links_until_retry_confirms_absence(): void
    {
        $this->postJson('/catalog-test/1/publish', ['token' => $this->token()])->assertOk();
        $token = $this->resetToken(); $this->deleteFails = true;
        $this->postJson('/catalog-test/1/reset', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'failed');
        $this->assertDatabaseHas('variants', ['id' => 1, 'shopify_variant_id' => 'gid://shopify/ProductVariant/1000']);
        $this->deleteFails = false; $this->deleteTimeout = true;
        $this->postJson('/catalog-test/1/reset', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'failed');
        $this->assertDatabaseCount('shopify_product_exports', 1);
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT'])->assertOk()->assertJsonPath('data.items.0.status', 'invalid');
        $this->postJson('/catalog-test/1/reset', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'reset');
        $this->assertDatabaseCount('shopify_product_exports', 0);
    }

    public function test_reset_rejects_publish_tokens_and_products_not_owned_by_export(): void
    {
        $publishToken = $this->token();
        $this->postJson('/catalog-test/1/reset', ['token' => $publishToken])->assertUnprocessable();
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT', 'mode' => 'reset'])->assertOk()->assertJsonPath('data.items.0.status', 'invalid');
        Http::assertNothingSent();
        $this->postJson('/catalog-test/1/publish', ['token' => $publishToken])->assertOk();
        $token = $this->resetToken();
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertUnprocessable();
        $this->remote['metafield']['value'] = 'other-export';
        $this->postJson('/catalog-test/1/reset', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'failed');
        $this->assertNotNull($this->remote);
        $this->assertDatabaseCount('shopify_product_exports', 1);
        $user = User::create(['name' => 'Sales']); $user->assignRole('sales'); $this->actingAs($user, 'api');
        $this->postJson('/catalog-test/1/reset', ['token' => $token])->assertForbidden();
    }

    public function test_preview_is_read_only_and_lists_title_and_sizes(): void
    {
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT'])->assertOk()
            ->assertJsonPath('data.connection.ready', true)->assertJsonPath('data.items.0.status', 'ready')
            ->assertJsonPath('data.items.0.variants.0.optionValues.0.name', 'S')->assertJsonCount(2, 'data.items.0.variants');
        Http::assertNothingSent(); $this->assertDatabaseCount('shopify_product_exports', 0);
    }

    public function test_publishes_only_catalog_fields_and_persists_variant_links_without_changing_stock(): void
    {
        $token = $this->token();
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'synced')->assertJsonPath('data.remote_status', 'DRAFT');
        Http::assertSent(function ($request) {
            if (!str_contains($request['query'], 'PublishErpProduct')) return false;
            $input = data_get($request->data(), 'variables.input');
            $this->assertEqualsCanonicalizing(['title', 'status', 'productOptions', 'variants', 'handle', 'metafields'], array_keys($input));
            foreach ($input['variants'] as $variant) $this->assertEqualsCanonicalizing(['sku', 'optionValues'], array_keys($variant));
            return $input['title'] === 'Blusa nueva' && $input['status'] === 'DRAFT';
        });
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 12, 'shopify_variant_id' => 'gid://shopify/ProductVariant/1000']);
        $this->assertCount(2, ShopifyProductExport::first()->variant_links);
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'synced');
        $this->assertSame(1, $this->creates); Http::assertSentCount(4);
    }

    public function test_retry_reconciles_a_timed_out_creation_instead_of_creating_a_duplicate(): void
    {
        $token = $this->token(); $this->timeout = true;
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'failed');
        $this->assertDatabaseHas('shopify_product_exports', ['status' => 'failed']);
        $this->timeout = false;
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'synced');
        $this->assertSame(1, $this->creates);
    }

    public function test_changed_products_invalid_tokens_other_stores_and_sales_users_cannot_publish(): void
    {
        $token = $this->token(); DB::table('products')->where('id', 1)->update(['name' => 'Changed']);
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertConflict();
        $this->postJson('/catalog-test/1/publish', ['token' => 'invalid'])->assertUnprocessable();
        $this->postJson('/catalog-test/preview', ['product_ids' => [2], 'target_status' => 'DRAFT'])->assertNotFound();
        $user = User::create(['name' => 'Sales']); $user->assignRole('sales'); $this->actingAs($user, 'api');
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_bad_combinations_imported_products_and_wrong_connection_are_explained_before_sending(): void
    {
        DB::table('option_values')->where('id', 2)->update(['value' => 'S']);
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT'])->assertOk()->assertJsonPath('data.items.0.status', 'invalid');
        DB::table('products')->where('id', 1)->update(['origin' => 'gid://shopify/Product/123']);
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT'])->assertOk()->assertJsonPath('data.items.0.status', 'linked')->assertJsonPath('data.items.0.token', null);
        config(['shopify.catalog_store_slug' => 'other']);
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT'])->assertOk()->assertJsonPath('data.connection.ready', false);
        Http::assertNothingSent();
    }

    public function test_active_status_too_many_products_and_duplicate_ids_are_rejected(): void
    {
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'ACTIVE'])->assertUnprocessable();
        $this->postJson('/catalog-test/preview', ['product_ids' => range(1, 26), 'target_status' => 'DRAFT'])->assertUnprocessable();
        $this->postJson('/catalog-test/preview', ['product_ids' => [1, 1], 'target_status' => 'DRAFT'])->assertUnprocessable();
        $this->postJson('/catalog-test/preview', ['product_ids' => [1], 'target_status' => 'DRAFT', 'mode' => 'inventory'])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_expired_review_and_concurrent_submission_do_not_send_mutations(): void
    {
        $token = $this->token();
        $this->travel(31)->minutes();
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertUnprocessable();
        $this->travelBack();
        $token = $this->token();
        ShopifyProductExport::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'product_id' => 1, 'store_id' => 1, 'shop_domain' => 'catalog-test.myshopify.com', 'handle' => 'erp-busy', 'status' => 'sending', 'attempted_at' => now()]);
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertConflict();
        Http::assertNothingSent();
    }

    public function test_an_existing_remote_handle_owned_elsewhere_is_never_overwritten(): void
    {
        $token = $this->token();
        $this->remote = ['id' => 'gid://shopify/Product/900', 'metafield' => ['value' => 'another-export']];
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'failed');
        $this->assertSame(0, $this->creates);
        $this->assertDatabaseHas('variants', ['id' => 1, 'shopify_variant_id' => null, 'stock' => 12]);
    }

    public function test_existing_shopify_skus_are_not_duplicated_or_overwritten(): void
    {
        $this->existingSkus = ['BL-S'];
        $token = $this->token();
        $this->postJson('/catalog-test/1/publish', ['token' => $token])->assertOk()->assertJsonPath('data.status', 'failed');
        $this->assertSame(0, $this->creates);
        $this->assertDatabaseHas('variants', ['id' => 1, 'shopify_variant_id' => null, 'stock' => 12]);
    }
}
