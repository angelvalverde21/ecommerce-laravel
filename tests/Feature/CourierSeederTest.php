<?php

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\Store;
use App\Services\Dashboard\Courier\CourierService;
use Database\Seeders\CourierSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourierSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'courier_test',
            'database.connections.courier_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => true,
            ],
        ]);

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');
            foreach ([
                'name', 'phone', 'type', 'primary', 'secondary', 'references',
                'latitud', 'longitud', 'url_maps', 'reception_hours', 'sunday_hours',
                'identity_id', 'document_number', 'district_id',
            ] as $column) {
                $table->string($column)->nullable();
            }
            $table->integer('status')->default(1);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('courier_test');
        parent::tearDown();
    }

    public function test_legacy_upgrade_and_repeated_seeding_preserve_courier_and_address_ids(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->integer('status')->default(1);
        });
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->boolean('is_cash_on_delivery')->default(false);
            $table->boolean('is_freight_collect')->default(false);
            $table->boolean('is_express_shipping')->default(false);
            $table->timestamps();
        });
        DB::table('users')->insert([
            'id' => 8,
            'name' => 'GAMA EXPRESS',
            'phone' => '925778997',
            'status' => 1,
        ]);
        DB::table('couriers')->insert([
            'id' => 12,
            'user_id' => 8,
            'is_cash_on_delivery' => 1,
            'is_freight_collect' => 1,
            'is_express_shipping' => 1,
        ]);
        DB::table('addresses')->insert([
            'id' => 35,
            'addressable_type' => Courier::class,
            'addressable_id' => 12,
            'name' => 'GAMA EXPRESS S.A.C.',
            'primary' => 'Galeria Gama',
            'district_id' => 150115,
        ]);

        $this->upgrade();

        $this->assertSame('GAMA EXPRESS', Courier::findOrFail(12)->name);
        $this->assertSame('express', Courier::findOrFail(12)->delivery_type);
        $this->seed(CourierSeeder::class);
        $firstIds = DB::table('addresses')->orderBy('id')->pluck('id')->all();
        $this->seed(CourierSeeder::class);

        $this->assertSame(5, Courier::count());
        $this->assertSame(539, DB::table('addresses')->count());
        $this->assertSame($firstIds, DB::table('addresses')->orderBy('id')->pluck('id')->all());
        $this->assertSame('agency', Courier::findOrFail(12)->delivery_type);
        $this->assertSame(12, (int) DB::table('addresses')->find(35)->addressable_id);
        $this->assertSame(8, (int) Courier::findOrFail(12)->user_id);
        $this->assertSame(1, DB::table('users')->count());
        $this->assertOrderSelector();
    }

    public function test_current_schema_also_supports_idempotent_seeding(): void
    {
        (require database_path('migrations/2026_01_25_183909_create_couriers_table.php'))->up();
        $this->upgrade();
        $this->seed(CourierSeeder::class);
        $this->seed(CourierSeeder::class);

        $this->assertSame(5, Courier::count());
        $this->assertSame(539, DB::table('addresses')->count());
        $this->assertOrderSelector();
    }

    private function upgrade(): void
    {
        (require database_path('migrations/2026_11_06_110000_align_legacy_couriers_for_orders.php'))->up();
    }

    private function assertOrderSelector(): void
    {
        $service = app(CourierService::class);
        $store = new Store();

        $this->assertSame(4, $service->agency($store)->total());
        $this->assertSame(1, $service->express($store)->total());
        $this->assertSame(534, Courier::where('name', 'SHALOM')->firstOrFail()->addresses()->count());
        $this->assertSame('SHALOM', $service->search($store, 'shalom')->sole()->name);
    }
}
