<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\OptionValueDashboardController;
use App\Models\Store;
use App\Services\Dashboard\OptionValue\OptionValueDeletionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OptionValueDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'option_deletion', 'database.connections.option_deletion' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', fn (Blueprint $table) => $table->id());
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
        });
        foreach ([
            '2025_12_26_171717_create_options_table.php', '2026_11_06_100000_add_archive_to_product_options.php', '2025_12_26_171730_create_option_values_table.php',
            '2025_12_26_171743_create_variants_table.php', '2025_12_26_180043_create_variant_option_values_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('stores')->insert([['id' => 1], ['id' => 2]]);
        DB::table('products')->insert([['id' => 1, 'store_id' => 1], ['id' => 2, 'store_id' => 1], ['id' => 3, 'store_id' => 2]]);
        DB::table('options')->insert([
            ['id' => 1, 'product_id' => 1, 'store_id' => 1, 'name' => 'size', 'label' => 'Tallas'],
            ['id' => 2, 'product_id' => 1, 'store_id' => 1, 'name' => 'color', 'label' => 'Color'],
            ['id' => 3, 'product_id' => 2, 'store_id' => 1, 'name' => 'size', 'label' => 'Tallas'],
            ['id' => 4, 'product_id' => 3, 'store_id' => 2, 'name' => 'size', 'label' => 'Tallas'],
        ]);
        DB::table('option_values')->insert([
            ['id' => 1, 'option_id' => 1, 'value' => 'M'], ['id' => 2, 'option_id' => 1, 'value' => 'L'],
            ['id' => 3, 'option_id' => 2, 'value' => 'Rojo'], ['id' => 4, 'option_id' => 2, 'value' => 'Azul'],
            ['id' => 5, 'option_id' => 4, 'value' => 'S'],
        ]);
        foreach ([[1, 1, 3, 1], [2, 1, 4, 3], [3, 2, 3, 1], [4, 2, 4, 1]] as [$id, $size, $color, $status]) {
            DB::table('variants')->insert(['id' => $id, 'product_id' => 1, 'sku' => 'V-'.$id, 'price' => 10, 'stock' => 0, 'status' => $status]);
            DB::table('variant_option_values')->insert([
                ['variant_id' => $id, 'option_id' => 1, 'option_value_id' => $size],
                ['variant_id' => $id, 'option_id' => 2, 'option_value_id' => $color],
            ]);
        }
        Route::delete('/test-products/{product}/options/{option}/values/{value}', fn ($product, $option, $value) => app(OptionValueDashboardController::class)->destroy(Store::findOrFail(1), $product, $option, $value, app(OptionValueDeletionService::class)));
    }

    private function url(int $value = 1): string
    {
        return '/test-products/1/options/1/values/'.$value;
    }

    public function test_deletes_active_and_inactive_variants_and_all_their_links_only_for_the_selected_value(): void
    {
        $this->deleteJson($this->url())->assertOk()->assertJsonPath('data.deleted_variant_ids', [1, 2]);
        $this->assertDatabaseMissing('option_values', ['id' => 1]);
        $this->assertDatabaseMissing('variants', ['id' => 1]);
        $this->assertDatabaseMissing('variants', ['id' => 2]);
        $this->assertDatabaseCount('variant_option_values', 4);
        $this->assertDatabaseHas('variants', ['id' => 3]);
        $this->assertDatabaseHas('option_values', ['id' => 3, 'value' => 'Rojo']);
        $this->assertDatabaseHas('options', ['id' => 1]);
        $this->deleteJson($this->url())->assertNotFound();
    }

    public function test_stock_in_any_affected_variant_blocks_the_entire_deletion(): void
    {
        DB::table('variants')->where('id', 2)->update(['stock' => 3]);
        $this->deleteJson($this->url())->assertUnprocessable()->assertJsonValidationErrors('value');
        $this->assertDatabaseHas('option_values', ['id' => 1]);
        $this->assertDatabaseCount('variants', 4);
        $this->assertDatabaseCount('variant_option_values', 8);
    }

    public function test_operational_history_is_preserved_including_nullable_cascading_and_future_foreign_keys(): void
    {
        foreach (['kardexes', 'acquire_variants', 'inventory_counts', 'future_usage'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName) {
                $table->id();
                if ($tableName === 'kardexes') {
                    $table->foreignId('variant_id')->nullable()->constrained('variants')->nullOnDelete();
                } elseif ($tableName === 'inventory_counts') {
                    $table->foreignId('variant_id')->constrained('variants')->restrictOnDelete();
                } else {
                    $table->foreignId('variant_id')->constrained('variants')->cascadeOnDelete();
                }
            });
            DB::table($tableName)->insert(['variant_id' => 1]);
            $this->deleteJson($this->url())->assertUnprocessable()->assertJsonValidationErrors('value');
            $this->assertDatabaseHas($tableName, ['variant_id' => 1]);
            $this->assertDatabaseCount('variants', 4);
            $this->assertDatabaseHas('option_values', ['id' => 1]);
            DB::table($tableName)->delete();
        }
    }

    public function test_rejects_other_stores_products_options_and_values(): void
    {
        foreach (['/test-products/2/options/1/values/1', '/test-products/1/options/3/values/1',
            '/test-products/1/options/2/values/1', '/test-products/3/options/4/values/5'] as $url) {
            $this->deleteJson($url)->assertNotFound();
        }
        $this->assertDatabaseCount('variants', 4);
        $this->assertDatabaseCount('option_values', 5);
    }

    public function test_deleting_the_last_size_does_not_create_an_unnamed_variant(): void
    {
        $this->deleteJson($this->url())->assertOk();
        $this->deleteJson($this->url(2))->assertOk();
        $this->assertDatabaseCount('variants', 0);
        $this->assertDatabaseCount('variant_option_values', 0);
        $this->assertDatabaseHas('option_values', ['id' => 3]);
        $this->assertDatabaseHas('options', ['id' => 1]);
    }

    public function test_failure_during_value_deletion_rolls_back_variants_and_links(): void
    {
        DB::unprepared("CREATE TRIGGER reject_value_deletion BEFORE DELETE ON option_values BEGIN SELECT RAISE(ABORT, 'Simulated failure'); END");
        $this->deleteJson($this->url())->assertStatus(500);
        $this->assertDatabaseHas('option_values', ['id' => 1]);
        $this->assertDatabaseCount('variants', 4);
        $this->assertDatabaseCount('variant_option_values', 8);
    }

    public function test_schema_discovery_runs_before_the_transaction_takes_row_locks(): void
    {
        $levels = [];
        DB::listen(function ($query) use (&$levels) {
            if (preg_match('/pragma_foreign_key_list|sqlite_master|sqlite_schema/i', $query->sql)) {
                $levels[] = DB::connection()->transactionLevel();
            }
        });
        $this->deleteJson($this->url())->assertOk();
        $this->assertNotEmpty($levels);
        $this->assertSame([0], array_values(array_unique($levels)));
    }
}
