<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\OrderDashboardController;
use App\Models\Address;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class StoreOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'order_test',
            'database.connections.order_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => true,
            ],
        ]);

        foreach (['stores', 'users', 'identities', 'districts', 'variants'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
            });
            DB::table($table)->insert(['id' => 1]);
        }

        (require database_path('migrations/2025_08_09_075500_create_addresses_table.php'))->up();
        (require database_path('migrations/2025_10_04_035549_create_orders_table.php'))->up();

        Address::create([
            'name' => 'Courier',
            'district_id' => 1,
            'addressable_type' => Store::class,
            'addressable_id' => 1,
        ]);

        $this->actingAs((new User())->forceFill(['id' => 1]));

        Route::post('/test-store-order', function (Request $request) {
            return app(OrderDashboardController::class)->store(Store::findOrFail(1), $request);
        });

        Route::get('/test-store-order', function () {
            return app(OrderDashboardController::class)->index(Store::findOrFail(1));
        });

        Route::get('/test-store-order/{order_id}', function (string $order_id) {
            return app(OrderDashboardController::class)->show(Store::findOrFail(1), $order_id);
        });
    }

    public function test_it_saves_the_order_and_its_polymorphic_address(): void
    {
        $payload = $this->payload();

        $this->postJson('/test-store-order', $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.variants', $payload['variants'])
            ->assertJsonPath('data.subtotal', '99.80')
            ->assertJsonPath('data.total', '114.80')
            ->assertJsonPath('data.is_cod', false)
            ->assertJsonPath('data.addresses.0.name', 'Angel Valverde');

        $order = Order::firstOrFail();
        $address = $order->addresses()->firstOrFail();

        $this->assertEquals(1, $order->store_id);
        $this->assertEquals(1, $order->user_id);
        $this->assertSame($payload['variants'], $order->variants);
        $this->assertSame('shipping', $address->purpose);
        $this->assertSame($order->getMorphClass(), $address->addressable_type);
        $this->assertTrue($address->addressable->is($order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('addresses', 2);
    }

    public function test_it_rolls_back_the_order_if_the_address_cannot_be_saved(): void
    {
        Log::spy();

        Address::creating(function () {
            throw new RuntimeException('Address insert failed');
        });

        try {
            $this->postJson('/test-store-order', $this->payload())
                ->assertStatus(500)
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'Error al crear la orden y su direccion')
                ->assertJsonPath('error', []);
        } finally {
            Address::flushEventListeners();
        }

        Log::shouldHaveReceived('info')->with(\Mockery::type(RuntimeException::class))->once();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('addresses', 1);
    }

    public function test_validation_errors_still_return_422(): void
    {
        $payload = $this->payload();
        $payload['variants'][0]['quantity'] = 0;

        $this->postJson('/test-store-order', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variants.0.quantity');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('addresses', 1);
    }

    public function test_it_returns_the_saved_order_with_variants_and_address(): void
    {
        $payload = $this->payload();
        $created = $this->postJson('/test-store-order', $payload)->assertOk();
        $orderId = $created->json('data.id');

        $this->getJson('/test-store-order/'.$orderId)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonPath('data.variants', $payload['variants'])
            ->assertJsonPath('data.courier_address_id', 1)
            ->assertJsonPath('data.courier_address.id', 1)
            ->assertJsonPath('data.courier_address.addressable_type', Store::class)
            ->assertJsonPath('data.courier_address.addressable_id', 1)
            ->assertJsonPath('data.courier_address.addressable.id', 1)
            ->assertJsonPath('data.delivery_cost', '15.00')
            ->assertJsonPath('data.total', '114.80')
            ->assertJsonPath('data.addresses.0.name', $payload['address']['name'])
            ->assertJsonCount(1, 'data.addresses');
    }

    public function test_show_returns_404_for_missing_orders_and_orders_from_other_stores(): void
    {
        $this->getJson('/test-store-order/999')->assertNotFound()->assertJsonPath('success', false);

        $created = $this->postJson('/test-store-order', $this->payload())->assertOk();
        $orderId = $created->json('data.id');
        DB::table('stores')->insert(['id' => 2]);
        Order::whereKey($orderId)->update(['store_id' => 2]);

        $this->getJson('/test-store-order/'.$orderId)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', []);
    }

    public function test_index_lists_only_store_orders_from_newest_to_oldest_with_their_relations(): void
    {
        $first = $this->postJson('/test-store-order', $this->payload())->assertOk()->json('data.id');
        $second = $this->postJson('/test-store-order', $this->payload())->assertOk()->json('data.id');
        $other = $this->postJson('/test-store-order', $this->payload())->assertOk()->json('data.id');
        DB::table('stores')->insert(['id' => 2]);
        Order::whereKey($other)->update(['store_id' => 2]);

        $this->getJson('/test-store-order')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $second)
            ->assertJsonPath('data.1.id', $first)
            ->assertJsonPath('data.0.variants', $this->payload()['variants'])
            ->assertJsonPath('data.0.addresses.0.name', 'Angel Valverde')
            ->assertJsonPath('data.0.courier_address.addressable_type', Store::class)
            ->assertJsonPath('data.0.courier_address.addressable.id', 1);
    }

    public function test_index_returns_an_empty_list_when_the_store_has_no_orders(): void
    {
        $this->getJson('/test-store-order')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }

    private function payload(): array
    {
        return [
            'address' => [
                'name' => 'Angel Valverde',
                'identity_id' => 1,
                'document_number' => '42412498',
                'phone' => '943402809',
                'primary' => 'Urb Albino Herrera MZ H LT 16',
                'district_id' => '1',
            ],
            'shipping' => [
                'courier_address_id' => 1,
                'delivery_cost' => 15,
                'is_cod' => false,
                'freight_collect' => false,
            ],
            'variants' => [[
                'id' => 1,
                'sku' => 'POLO-NEGRO-M',
                'name' => 'Polo clasico',
                'variant_option_values' => [[
                    'id' => 1,
                    'variant_id' => 1,
                    'option_id' => 580,
                    'option_value_id' => 1271,
                    'option_value' => [
                        'id' => 1271,
                        'option_id' => 580,
                        'value' => '26',
                        'sort_order' => 1,
                        'created_at' => '2026-10-29T02:43:04.000000Z',
                        'updated_at' => '2026-10-29T02:43:04.000000Z',
                    ],
                ]],
                'quantity' => 2,
                'price' => 49.9,
            ]],
        ];
    }
}
