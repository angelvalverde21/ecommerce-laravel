<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\AddressDashboardController;
use App\Http\Controllers\Api\Dashboard\CourierDashboardController;
use App\Models\Courier;
use App\Models\Store;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourierManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'courier_management',
            'database.connections.courier_management' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => true,
            ],
        ]);
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
        });
        DB::table('stores')->insert(['id' => 1]);
        // Deliberately no users/store_user tables: this workflow must work without them.
        (require database_path('migrations/2026_01_25_183909_create_couriers_table.php'))->up();
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('department_id')->constrained();
        });
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('province_id')->constrained();
        });
        DB::table('departments')->insert(['id' => 1, 'name' => 'Lima']);
        DB::table('provinces')->insert(['id' => 1, 'name' => 'Lima', 'department_id' => 1]);
        DB::table('districts')->insert(['id' => 150115, 'name' => 'La Victoria', 'province_id' => 1]);
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');
            foreach (['name', 'phone', 'primary', 'secondary', 'references', 'document_number', 'identity_id'] as $column) {
                $table->string($column)->nullable();
            }
            $table->foreignId('district_id')->constrained();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_address_id')->constrained('addresses');
        });

        $store = fn () => Store::findOrFail(1);
        $controller = fn () => app(CourierDashboardController::class);
        Route::get('/courier-test', fn (Request $request) => $controller()->index($store(), $request));
        Route::post('/courier-test', fn (Request $request) => $controller()->store($store(), $request));
        Route::get('/courier-test/agency', fn () => $controller()->agency($store()));
        Route::get('/courier-test/search/{search?}', fn (string $search = '') => $controller()->search($store(), $search));
        Route::get('/courier-test/{id}', fn (int $id) => $controller()->show($store(), $id));
        Route::get('/courier-test/{id}/addresses', fn (Request $request, int $id) => $controller()->addresses($store(), $request, $id));
        Route::put('/courier-test/{id}', fn (Request $request, int $id) => $controller()->update($request, $store(), $id));
        Route::delete('/courier-test/{id}', fn (int $id) => $controller()->destroy($store(), $id));
        Route::post('/courier-office-test', fn (Request $request) => app(AddressDashboardController::class)->store($store(), $request));
        Route::put('/courier-office-test/{id}', fn (Request $request, int $id) => app(AddressDashboardController::class)->update($request, $store(), $id));
    }

    protected function tearDown(): void
    {
        DB::purge('courier_management');
        parent::tearDown();
    }

    public function test_create_edit_and_search_use_courier_fields_without_users(): void
    {
        $id = $this->postJson('/courier-test', $this->courierData())->assertCreated()
            ->assertJsonPath('data.name', 'Envíos del Sur')
            ->assertJsonPath('data.is_cod', true)
            ->assertJsonMissingPath('data.user_id')
            ->json('data.id');

        $this->putJson('/courier-test/' . $id, [
            'name' => 'Envíos Express',
            'is_cod' => false,
            'freight_collect' => true,
            'delivery_type' => 'express',
            'phone' => null,
            'user_id' => 99,
        ])->assertOk()->assertJsonPath('data.is_cod', false)
            ->assertJsonPath('data.freight_collect', true)->assertJsonPath('data.phone', null);

        $this->getJson('/courier-test?search=Express&delivery_type=express&status=1')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.name', 'Envíos Express');
        $this->getJson('/courier-test/search/Express')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->getJson('/courier-test/' . $id)->assertOk()->assertJsonPath('data.name', 'Envíos Express');
        $this->assertSame(1, Courier::count());
    }

    public function test_invalid_requests_return_validation_errors_and_missing_records_return_404(): void
    {
        $this->postJson('/courier-test', ['name' => '  ', 'delivery_type' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'delivery_type', 'is_cod', 'freight_collect']);
        $this->putJson('/courier-test/999', ['name' => 'Missing'])->assertNotFound();
        $this->getJson('/courier-test/999')->assertNotFound();
        $this->assertSame(0, Courier::count());
    }

    public function test_offices_belong_to_couriers_and_blocking_preserves_order_history(): void
    {
        $courier = Courier::create($this->courierData());
        $office = [
            'addressable_type' => 'courier',
            'addressable_id' => $courier->id,
            'name' => 'Oficina Lima',
            'primary' => 'Av. Lima 123',
            'district_id' => 150115,
            'phone' => null,
            'document_number' => null,
            'identity_id' => null,
            'secondary' => null,
            'references' => null,
        ];
        $addressId = $this->postJson('/courier-office-test', $office)->assertOk()
            ->assertJsonPath('data.district.name', 'La Victoria')->json('data.id');
        $this->assertSame($courier->id, (int) DB::table('addresses')->find($addressId)->addressable_id);
        $this->assertSame(Courier::class, DB::table('addresses')->find($addressId)->addressable_type);

        $this->putJson('/courier-office-test/' . $addressId, [...$office, 'primary' => 'Av. Lima 456'])
            ->assertOk()->assertJsonPath('data.primary', 'Av. Lima 456');

        $other = Courier::create([...$this->courierData(), 'name' => 'Otro courier']);
        $this->putJson('/courier-office-test/' . $addressId, [...$office, 'addressable_id' => $other->id])
            ->assertNotFound();

        DB::table('orders')->insert(['courier_address_id' => $addressId]);
        $this->deleteJson('/courier-test/' . $courier->id)->assertOk()->assertJsonPath('data.status', 0);
        $this->getJson('/courier-test/agency')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/courier-test?status=0')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/courier-test/' . $courier->id)->assertOk()->assertJsonPath('data.addresses_count', 1)
            ->assertJsonMissingPath('data.addresses');
        $this->getJson('/courier-test/' . $courier->id . '/addresses')
            ->assertOk()->assertJsonPath('data.0.id', $addressId);
        $this->assertSame($addressId, (int) DB::table('orders')->first()->courier_address_id);
    }

    public function test_offices_are_fetched_twenty_at_a_time_and_search_covers_all_pages(): void
    {
        $courier = Courier::create($this->courierData());
        foreach (range(1, 45) as $number) {
            $courier->addresses()->create([
                'name' => 'Oficina ' . $number,
                'primary' => 'Av. Lima ' . $number,
                'district_id' => 150115,
                'sort_order' => $number,
            ]);
        }
        $other = Courier::create([...$this->courierData(), 'name' => 'Otro']);
        $other->addresses()->create(['name' => 'Otra oficina', 'district_id' => 150115]);

        $this->getJson('/courier-test/' . $courier->id)->assertOk()
            ->assertJsonPath('data.addresses_count', 45)->assertJsonMissingPath('data.addresses');
        $this->getJson('/courier-test')->assertOk()->assertJsonMissingPath('data.0.addresses');
        $this->getJson('/courier-test/' . $courier->id . '/addresses')->assertOk()
            ->assertJsonCount(20, 'data')->assertJsonPath('total', 45)->assertJsonPath('last_page', 3);
        $this->getJson('/courier-test/' . $courier->id . '/addresses?page=2')->assertOk()
            ->assertJsonCount(20, 'data')->assertJsonPath('data.0.name', 'Oficina 21');
        $this->getJson('/courier-test/' . $courier->id . '/addresses?page=3')->assertOk()
            ->assertJsonCount(5, 'data')->assertJsonPath('data.0.name', 'Oficina 41');
        $this->getJson('/courier-test/' . $courier->id . '/addresses?search=Oficina%2045')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Oficina 45');
        $this->getJson('/courier-test/' . $courier->id . '/addresses?search=Victoria')->assertOk()
            ->assertJsonPath('total', 45);
    }

    private function courierData(): array
    {
        return [
            'name' => 'Envíos del Sur',
            'phone' => '999888777',
            'is_cod' => true,
            'freight_collect' => false,
            'delivery_type' => 'agency',
            'status' => 1,
        ];
    }
}
