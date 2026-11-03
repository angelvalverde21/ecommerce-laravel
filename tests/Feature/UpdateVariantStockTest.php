<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\VariantDashboardController;
use App\Models\Inventory;
use App\Models\Kardex;
use App\Models\Store;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class UpdateVariantStockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'stock_test',
            'database.connections.stock_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => true,
            ],
        ]);

        foreach (['stores', 'users'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
            });
            DB::table($table)->insert(['id' => 1]);
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained();
        });
        DB::table('products')->insert(['id' => 1, 'store_id' => 1]);

        foreach ([
            '2025_12_26_171743_create_variants_table.php',
            '2025_12_26_171717_create_options_table.php',
            '2025_12_26_171730_create_option_values_table.php',
            '2025_12_26_180043_create_variant_option_values_table.php',
            '2026_04_10_140559_create_inventories_table.php',
            '2026_02_11_140029_create_kardexes_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Variant::create(['product_id' => 1, 'sku' => 'TEST', 'price' => 10, 'stock' => 4]);
        $this->actingAs((new User())->forceFill(['id' => 1]));

        Route::put('/test-variants/{variant_id}', function (Request $request, string $variant_id) {
            return app(VariantDashboardController::class)->update(Store::findOrFail(1), $request, $variant_id);
        });

        Route::get('/test-variants/{variant_id}', function (string $variant_id) {
            return app(VariantDashboardController::class)->show(Store::findOrFail(1), $variant_id);
        });
    }

    public function test_it_adds_and_withdraws_stock_and_records_inventory_kardexes(): void
    {
        $updated = $this->putJson('/test-variants/1', $this->movement('in', 5))
            ->assertOk()
            ->assertJsonPath('data.stock', 9)
            ->assertJsonPath('data.kardexes.0.quantity', 5)
            ->assertJsonPath('data.kardexes.0.direction', 'in');

        $shown = $this->getJson('/test-variants/1')->assertOk();
        $this->assertSame($shown->json('data'), $updated->json('data'));

        $kardex = Kardex::firstOrFail();
        $this->assertInstanceOf(Inventory::class, $kardex->kardexable);
        $this->assertEquals(1, $kardex->kardexable->user_id);
        $this->assertEquals(1, $kardex->store_id);
        $this->assertEquals(1, $kardex->product_id);

        $this->putJson('/test-variants/1', $this->movement('out', 9))
            ->assertOk()->assertJsonPath('data.stock', 0);

        $this->assertDatabaseHas('kardexes', ['direction' => 'out', 'quantity' => 9]);
        $this->assertDatabaseCount('kardexes', 2);
        $this->assertDatabaseCount('inventories', 2);
    }

    public function test_it_rejects_insufficient_stock_and_invalid_movements_without_writes(): void
    {
        foreach ([
            $this->movement('out', 5),
            $this->movement('in', 0),
            $this->movement('in', -1),
            $this->movement('invalid', 1),
            [...$this->movement('in', 1), 'variant_id' => 2],
        ] as $payload) {
            $this->putJson('/test-variants/1', $payload)->assertUnprocessable();
        }

        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4]);
        $this->assertDatabaseCount('kardexes', 0);
        $this->assertDatabaseCount('inventories', 0);
    }

    public function test_it_rejects_variants_from_another_store(): void
    {
        DB::table('stores')->insert(['id' => 2]);
        DB::table('products')->where('id', 1)->update(['store_id' => 2]);

        $this->putJson('/test-variants/1', $this->movement('in', 5))->assertNotFound();
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4]);
        $this->assertDatabaseCount('kardexes', 0);
    }

    public function test_a_kardex_failure_rolls_back_stock_and_inventory(): void
    {
        Log::spy();
        Kardex::creating(function () {
            throw new RuntimeException('Kardex insert failed');
        });

        try {
            $this->putJson('/test-variants/1', $this->movement('in', 5))
                ->assertStatus(500)->assertJsonPath('success', false);
        } finally {
            Kardex::flushEventListeners();
        }

        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 4]);
        $this->assertDatabaseCount('kardexes', 0);
        $this->assertDatabaseCount('inventories', 0);
        Log::shouldHaveReceived('error')->once();
    }

    private function movement(string $direction, int $quantity): array
    {
        return ['variant_id' => 1, 'direction' => $direction, 'quantity' => $quantity, 'comment' => 'Conteo parcial'];
    }
}
