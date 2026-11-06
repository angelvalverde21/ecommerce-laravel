<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\OptionValueDashboardController;
use App\Models\Option;
use App\Models\Store;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OptionValueDuplicatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'option_duplicates', 'database.connections.option_duplicates' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', fn (Blueprint $table) => $table->id());
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('store_id');
        });
        foreach ([
            '2025_12_26_171717_create_options_table.php', '2026_11_06_100000_add_archive_to_product_options.php',
            '2025_12_26_171730_create_option_values_table.php',
            '2025_12_26_171743_create_variants_table.php',
            '2025_12_26_180043_create_variant_option_values_table.php',
        ] as $migration) { (require database_path('migrations/'.$migration))->up(); }
        DB::table('stores')->insert([['id' => 1], ['id' => 2]]);
        DB::table('products')->insert([['id' => 1, 'store_id' => 1], ['id' => 2, 'store_id' => 1], ['id' => 3, 'store_id' => 2]]);
        foreach ([1 => 1, 2 => 1, 3 => 2] as $productId => $storeId) {
            $option = new Option(['product_id' => $productId, 'store_id' => $storeId, 'name' => 'size', 'label' => 'Tallas']);
            $option->saveQuietly();
        }
        DB::table('option_values')->insert(['option_id' => 1, 'value' => 'M']);
        Route::post('/test-option-values', fn (Request $request) => app(OptionValueDashboardController::class)->store(Store::findOrFail(1), $request));
    }

    public function test_duplicate_values_are_rejected_without_new_values_or_variants(): void
    {
        foreach (['M', 'm', ' M ', ' m '] as $value) {
            $this->postJson('/test-option-values', ['option_id' => 1, 'value' => $value])
                ->assertUnprocessable()->assertJsonValidationErrors('value');
        }
        $this->assertDatabaseCount('option_values', 1);
        $this->assertDatabaseCount('variants', 0);
    }

    public function test_new_values_are_trimmed_saved_and_then_rejected_on_repeat(): void
    {
        $this->postJson('/test-option-values', ['option_id' => 1, 'value' => ' L '])->assertOk()->assertJsonPath('data.value', 'L');
        $count = DB::table('variants')->count();
        $this->postJson('/test-option-values', ['option_id' => 1, 'value' => 'l'])->assertUnprocessable();
        $this->assertDatabaseCount('option_values', 2);
        $this->assertDatabaseCount('variants', $count);
    }

    public function test_same_value_is_allowed_in_another_option_but_not_another_store(): void
    {
        $this->postJson('/test-option-values', ['option_id' => 2, 'value' => 'M'])->assertOk();
        $this->postJson('/test-option-values', ['option_id' => 3, 'value' => 'M'])->assertNotFound();
        $this->postJson('/test-option-values', ['option_id' => 1, 'value' => ' '])->assertUnprocessable();
        $this->assertDatabaseCount('option_values', 2);
    }
}
