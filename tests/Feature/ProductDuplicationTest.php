<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Models\Product;
use App\Models\Store;
use App\Services\Dashboard\Product\ProductDuplicationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductDuplicationTest extends TestCase
{
    private Product $source;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'duplicate_test', 'database.connections.duplicate_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Storage::fake('public');
        Http::preventStrayRequests();
        foreach (['users', 'stores', 'categories'] as $table) {
            Schema::create($table, fn (Blueprint $blueprint) => $blueprint->id());
            DB::table($table)->insert(['id' => 1]);
        }
        foreach ([
            '0001_03_01_000200_create_products_table.php',
            '0001_03_01_000205_create_colors_table.php',
            '2025_08_23_065925_create_sizes_table.php',
            '2025_09_30_064608_create_color_size_table.php',
            '2025_08_09_063554_create_images_table.php',
            '2025_12_19_051311_create_prices_table.php',
            '2026_11_05_100000_add_quantity_price_total_to_prices_table.php',
            '2026_11_05_110000_create_price_labels_table.php',
            '2025_12_26_171717_create_options_table.php', '2026_11_06_100000_add_archive_to_product_options.php',
            '2025_12_26_171730_create_option_values_table.php',
            '2025_12_26_171743_create_variants_table.php',
            '2026_11_16_100000_add_shopify_variant_id_to_variants.php',
            '2025_12_26_180043_create_variant_option_values_table.php',
            '2025_12_29_164504_create_attributes_table.php',
            '2025_12_29_164557_create_attribute_values_table.php',
        ] as $migration) { (require database_path('migrations/'.$migration))->up(); }

        $this->source = Product::create([
            'name' => 'Short verano verde', 'slug' => 'short-verano-verde', 'store_id' => 1, 'user_id' => 1,
            'price' => 6, 'compare_at_price' => 8, 'body' => 'Algodón', 'tags' => 'verano,short',
            'quantity' => 20, 'sku' => 'SHORT-ORIGINAL', 'origin' => 'shopify-123',
            'online_store_url' => 'https://shop.example/short', 'barcode' => '123456',
        ]);
        $option = $this->source->options()->getRelated()->newInstance(['name' => 'size', 'label' => 'Tallas', 'store_id' => 1]);
        $this->source->options()->saveQuietly($option);
        $child = $this->source->options()->getRelated()->newInstance(['name' => 'color', 'label' => 'Color', 'parent_id' => $option->id, 'store_id' => 1]);
        $this->source->options()->saveQuietly($child);
        foreach (['S', 'M', 'L'] as $i => $size) {
            $value = $option->option_values()->getRelated()->newInstance(['value' => $size, 'sort_order' => $i]);
            $option->option_values()->saveQuietly($value);
            $variant = $this->source->variants()->create(['sku' => 'SHORT-'.$size, 'price' => 6, 'stock' => 10]);
            $variant->variant_option_values()->create(['option_id' => $option->id, 'option_value_id' => $value->id]);
        }
        $this->source->variants()->create(['sku' => 'INACTIVE', 'price' => 5, 'stock' => 2, 'status' => 0]);
        $attribute = $this->source->attributes()->create(['name' => 'material', 'value' => 'Algodón', 'store_id' => 1]);
        DB::table('attribute_values')->insert(['attribute_id' => $attribute->id, 'value' => 'Suave', 'sort_order' => 1]);
        $label = \App\Models\PriceLabel::forName('Mayorista');
        $this->source->prices()->create(['name' => 'Mayorista', 'quantity' => 3, 'price' => 4, 'total' => 12, 'value' => 4, 'price_label_id' => $label->id]);
        $size = $this->source->sizes()->create(['name' => 'Legacy S', 'sku' => 'SIZE-S', 'quantity' => 5]);
        $size->prices()->create(['name' => 'Normal', 'quantity' => 1, 'price' => 6, 'total' => 6, 'value' => 6]);
        $color = $this->source->colors()->create(['name' => 'Verde', 'sku' => 'GREEN', 'quantity' => 5]);
        $color->sizes()->attach($size->id, ['price' => 6, 'quantity' => 5, 'sku' => 'GREEN-S']);
        foreach (['thumbnail', 'medium', 'large'] as $field) {
            Storage::disk('public')->put('images/products/'.$field.'.png', 'original-'.$field);
        }
        $this->source->images()->create(['name' => 'short.png', 'thumbnail' => 'products/thumbnail.png', 'medium' => 'products/medium.png', 'large' => 'products/large.png']);
        $this->source->refresh();

        Route::post('/test-duplicate/{product}', fn (Request $request, $product) => app(ProductDashboardController::class)->duplicate(
            Store::findOrFail(1), $product, $request, app(ProductDuplicationService::class)
        ));
    }

    public function test_duplicate_has_independent_properties_options_prices_and_no_images(): void
    {
        $response = $this->postJson('/test-duplicate/'.$this->source->id, ['name' => '  Short verano azul  '])->assertOk();
        $copy = Product::findOrFail($response->json('data.id'));
        $this->assertNotEquals($this->source->id, $copy->id);
        $this->assertSame('Short verano azul', $copy->name);
        foreach (['body', 'tags', 'price', 'compare_at_price', 'user_id', 'store_id', 'status'] as $field) {
            $this->assertEquals($this->source->$field, $copy->$field, $field);
        }
        $this->assertSame(0, $copy->quantity);
        $this->assertNull($copy->origin);
        $this->assertNull($copy->online_store_url);
        $this->assertNotEquals($this->source->sku, $copy->sku);
        $originalIds = $this->source->variants()->withoutGlobalScopes()->pluck('id');
        $variants = $copy->variants()->withoutGlobalScopes()->get();
        $this->assertCount(4, $variants);
        $this->assertEmpty($originalIds->intersect($variants->pluck('id')));
        $this->assertEquals([0], $variants->pluck('stock')->unique()->values()->all());
        $this->assertNotEquals($this->source->variants->pluck('sku'), $copy->variants->pluck('sku'));
        $this->assertEquals(['S', 'M', 'L'], $copy->options->firstWhere('name', 'size')->option_values->sortBy('sort_order')->pluck('value')->values()->all());
        $this->assertEquals($copy->options->firstWhere('name', 'size')->id, $copy->options->firstWhere('name', 'color')->parent_id);
        foreach ($copy->variants as $variant) {
            foreach ($variant->variant_option_values as $link) {
                $this->assertContains($link->option_id, $copy->options->pluck('id')->all());
                $this->assertNotContains($link->option_value_id, $this->source->options->firstWhere('name', 'size')->option_values->pluck('id')->all());
            }
        }
        $this->assertNotEquals($this->source->prices->first()->id, $copy->prices->first()->id);
        $this->assertEquals('12.00', $copy->prices->first()->total);
        $this->assertSame($this->source->prices->first()->price_label_id, $copy->prices->first()->price_label_id);
        $this->assertNotEquals($this->source->attributes->first()->id, $copy->attributes->first()->id);
        $this->assertSame('Suave', $copy->attributes->first()->attribute_values->first()->value);
        $this->assertNotEquals($this->source->sizes->first()->id, $copy->sizes->first()->id);
        $this->assertEquals(0, $copy->sizes->first()->quantity);
        $this->assertEquals('6.00', $copy->sizes->first()->prices->first()->total);
        $this->assertEquals($copy->sizes->first()->id, $copy->colors->first()->sizes->first()->id);
        $this->assertEquals(0, DB::table('color_size')->where('color_id', $copy->colors->first()->id)->value('quantity'));
        $this->assertCount(0, $copy->images);
        $response->assertJsonPath('data.image', null);
        $response->assertJsonPath('data.stock_total', 0);
        $this->assertCount(1, $this->source->images);
        foreach (['thumbnail', 'medium', 'large'] as $field) {
            $this->assertSame('original-'.$field, Storage::disk('public')->get('images/products/'.$field.'.png'));
        }
        $copy->options->firstWhere('name', 'size')->option_values->firstWhere('value', 'S')->update(['value' => 'XS']);
        $this->assertSame('S', $this->source->options->firstWhere('name', 'size')->option_values->firstWhere('value', 'S')->fresh()->value);
        $copy->prices->first()->update(['price' => 10]);
        $this->assertEquals('4.00', $this->source->prices->first()->fresh()->price);
        $this->assertEquals(10, $this->source->variants->first()->fresh()->stock);
    }

    public function test_copying_again_uses_unique_identifiers_even_for_the_same_name(): void
    {
        $first = $this->postJson('/test-duplicate/1', ['name' => 'Short azul'])->assertOk()->json('data.id');
        $second = $this->postJson('/test-duplicate/1', ['name' => 'Short azul'])->assertOk()->json('data.id');
        $this->assertNotEquals(Product::find($first)->slug, Product::find($second)->slug);
        $this->assertNotEquals(Product::find($first)->sku, Product::find($second)->sku);
    }

    public function test_duplicate_does_not_reuse_the_original_shopify_variant_links(): void
    {
        $this->source->variants()->update(['shopify_variant_id' => 'gid://shopify/ProductVariant/123']);
        $id = $this->postJson('/test-duplicate/1', ['name' => 'Nueva copia'])->assertOk()->json('data.id');
        $this->assertFalse(Product::findOrFail($id)->variants()->withoutGlobalScopes()->whereNotNull('shopify_variant_id')->exists());
        $this->assertSame('gid://shopify/ProductVariant/123', $this->source->variants()->first()->shopify_variant_id);
    }

    public function test_invalid_names_and_other_stores_are_rejected(): void
    {
        $this->postJson('/test-duplicate/1', ['name' => ' '])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/test-duplicate/1', ['name' => str_repeat('x', 256)])->assertUnprocessable();
        DB::table('stores')->insert(['id' => 2]);
        $this->source->update(['store_id' => 2]);
        $this->postJson('/test-duplicate/1', ['name' => 'Copia'])->assertNotFound();
        $this->assertDatabaseCount('products', 1);
    }

    public function test_adding_a_size_to_the_copy_keeps_existing_variant_ids(): void
    {
        $id = $this->postJson('/test-duplicate/1', ['name' => 'Short azul'])->assertOk()->json('data.id');
        $copy = Product::findOrFail($id);
        $existing = $copy->variants()->pluck('id');
        $copy->options()->where('name', 'size')->firstOrFail()->option_values()->create(['value' => 'XL', 'sort_order' => 4]);
        $current = $copy->variants()->pluck('id');
        $this->assertCount(4, $current);
        $this->assertCount(3, $existing->intersect($current));
        $this->assertCount(3, $this->source->variants()->get());
    }

    public function test_archived_options_and_their_variant_links_are_copied_without_reappearing_as_active(): void
    {
        $color = $this->source->allOptions()->where('name', 'color')->firstOrFail();
        $color->update(['archived_at' => now()]);
        $valueId = DB::table('option_values')->insertGetId(['option_id' => $color->id, 'value' => 'Verde']);
        $variant = $this->source->variants()->firstOrFail();
        $variant->update(['status' => 3]);
        $variant->variant_option_values()->create(['option_id' => $color->id, 'option_value_id' => $valueId]);
        $id = $this->postJson('/test-duplicate/1', ['name' => 'Copia con archivo'])->assertOk()->json('data.id');
        $copy = Product::findOrFail($id);
        $this->assertFalse($copy->options()->where('name', 'color')->exists());
        $archivedColor = $copy->allOptions()->where('name', 'color')->firstOrFail();
        $this->assertNotNull($archivedColor->archived_at);
        $this->assertNotEquals($color->id, $archivedColor->id);
        $this->assertSame('Verde', $archivedColor->option_values->first()->value);
        $this->assertDatabaseHas('variant_option_values', ['option_id' => $archivedColor->id, 'option_value_id' => $archivedColor->option_values->first()->id]);
    }

    public function test_missing_source_images_do_not_block_duplication_or_create_files(): void
    {
        $before = Storage::disk('public')->allFiles();
        $this->source->images()->create(['large' => 'products/missing.png']);
        $id = $this->postJson('/test-duplicate/1', ['name' => 'Copia'])->assertOk()->json('data.id');
        $this->assertDatabaseCount('products', 2);
        $this->assertCount(0, Product::findOrFail($id)->images);
        $this->assertCount(2, $this->source->images);
        $this->assertEquals($before, Storage::disk('public')->allFiles());
        Http::assertNothingSent();
    }

    public function test_remote_images_are_not_downloaded_or_linked_to_the_copy(): void
    {
        $before = Storage::disk('public')->allFiles();
        foreach (['https://8.8.8.8/short.png', 'http://127.0.0.1/private'] as $url) {
            $this->source->images()->update(['thumbnail' => $url, 'medium' => $url, 'large' => $url]);
            $id = $this->postJson('/test-duplicate/1', ['name' => 'Copia'])->assertOk()->json('data.id');
            $this->assertCount(0, Product::findOrFail($id)->images);
            $this->assertSame($url, $this->source->images()->first()->large);
        }
        $this->assertDatabaseCount('images', 1);
        $this->assertEquals($before, Storage::disk('public')->allFiles());
        Http::assertNothingSent();
    }
}
