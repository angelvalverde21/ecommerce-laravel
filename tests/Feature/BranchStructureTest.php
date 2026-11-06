<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\BranchDashboardController;
use App\Http\Controllers\Api\Dashboard\AddressDashboardController;
use App\Http\Controllers\Api\Dashboard\WarehouseDashboardController;
use App\Http\Controllers\Api\Dashboard\LocationDashboardController;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Store;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BranchStructureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'branches_test', 'database.connections.branches_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
        });
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('addressable');
            foreach (['name', 'primary', 'phone', 'document_number', 'identity_id', 'secondary', 'references', 'type', 'latitud', 'longitud', 'url_maps', 'reception_hours', 'sunday_hours'] as $column) {
                $table->string($column)->nullable();
            }
            $table->unsignedBigInteger('district_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('status')->default(1);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        DB::table('stores')->insert([['id' => 1], ['id' => 2]]);
        foreach (['2026_11_03_094309_create_branches_table.php', '2026_11_03_095627_create_warehouses_table.php', '2026_11_06_130000_add_subtitle_to_warehouses_table.php', '2026_11_03_100000_create_locations_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('inventories', function (Blueprint $table) { $table->id(); $table->nullableMorphs('inventoriable'); });
        foreach (['inventory_counts', 'inventory_location_progress'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) { $table->id(); $table->foreignId('location_id')->constrained('locations')->restrictOnDelete(); });
        }
        Route::delete('/test-locations/{id}', fn (string $id) => app(LocationDashboardController::class)->destroy(Store::findOrFail(1), $id));
        foreach (['branches' => BranchDashboardController::class, 'warehouses' => WarehouseDashboardController::class, 'locations' => LocationDashboardController::class] as $name => $controller) {
            Route::get('/test-'.$name, fn (Request $request) => app($controller)->index(Store::findOrFail(1), $request));
            Route::put('/test-'.$name.'/{id}', fn (Request $request, string $id) => app($controller)->update(Store::findOrFail(1), $request, $id));
            Route::post('/test-'.$name, fn (Request $request) => app($controller)->store(Store::findOrFail(1), $request));
            Route::get('/test-'.$name.'/{id}', fn (string $id) => app($controller)->show(Store::findOrFail(1), $id));
        }
    }

    private function branch(int $store = 1): Branch
    {
        return Branch::create(['store_id' => $store, 'name' => 'Sucursal', 'code' => 'S-'.Branch::count()]);
    }

    public function test_branch_list_identifies_the_current_store(): void
    {
        DB::table('stores')->where('id', 1)->update(['name' => 'Sorelle', 'slug' => 'sorelle']);

        $this->getJson('/test-branches')->assertOk()
            ->assertJsonPath('store.id', 1)
            ->assertJsonPath('store.name', 'Sorelle')
            ->assertJsonPath('store.slug', 'sorelle');
    }

    public function test_warehouse_subtitle_is_saved_separately_from_the_name(): void
    {
        $branch = $this->branch();
        $id = $this->postJson('/test-warehouses', [
            'branch_id' => $branch->id,
            'name' => 'Tienda 420',
            'subtitle' => 'Showroom',
        ])->assertCreated()->assertJsonPath('data.name', 'Tienda 420')
            ->assertJsonPath('data.subtitle', 'Showroom')
            ->assertJsonPath('data.code', 'tienda_420')->json('data.id');

        $this->getJson('/test-warehouses?branch_id=' . $branch->id)->assertOk()
            ->assertJsonPath('data.0.subtitle', 'Showroom');
        $this->putJson('/test-warehouses/' . $id, [
            'branch_id' => $branch->id,
            'name' => 'Tienda 420',
            'code' => 'tienda_420',
            'subtitle' => null,
        ])->assertOk()->assertJsonPath('data.subtitle', null);
    }

    public function test_subtitle_migration_separates_legacy_parentheses_and_preserves_identifiers(): void
    {
        $migration = require database_path('migrations/2026_11_06_130000_add_subtitle_to_warehouses_table.php');
        $migration->down();
        $warehouse = $this->warehouse($this->branch());
        DB::table('warehouses')->where('id', $warehouse->id)->update(['name' => 'Tienda 420 (Showroom)']);
        $migration->up();

        $record = DB::table('warehouses')->find($warehouse->id);
        $this->assertSame('Tienda 420', $record->name);
        $this->assertSame('Showroom', $record->subtitle);
        $this->assertSame($warehouse->code, $record->code);
    }

    public function test_generates_snake_case_codes_when_the_client_leaves_them_empty(): void
    {
        $branchId = $this->postJson('/test-branches', ['name' => 'Sucursal Miraflores'])
            ->assertCreated()->assertJsonPath('data.code', 'sucursal_miraflores')->json('data.id');
        $warehouseId = $this->postJson('/test-warehouses', [
            'branch_id' => $branchId,
            'name' => 'Almacén Ñandú / Lima 02',
            'code' => '',
        ])->assertCreated()->assertJsonPath('data.code', 'almacen_nandu_lima_02')->json('data.id');

        $this->postJson('/test-locations', [
            'warehouse_id' => $warehouseId,
            'name' => 'Estante Número 3',
        ])->assertCreated()->assertJsonPath('data.code', 'estante_numero_3');

        $this->postJson('/test-branches', ['name' => 'Sucursal Miraflores'])
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->postJson('/test-branches', ['name' => str_repeat('Principal ', 20)])
            ->assertCreated()->assertJsonPath('data.code', fn ($code) => strlen($code) <= 50 && !str_ends_with($code, '_'));
    }

    public function test_warehouses_inherit_the_branch_address_and_cannot_override_it(): void
    {
        $store = Store::findOrFail(1);
        $address = $store->addresses()->create(['name' => 'Local principal', 'primary' => 'Av. Lima 123']);
        $newAddress = $store->addresses()->create(['name' => 'Nuevo local', 'primary' => 'Av. Lima 456']);
        $branch = $this->branch();
        $branch->update(['address_id' => $address->id]);

        $this->getJson('/test-branches/' . $branch->id)->assertOk()
            ->assertJsonPath('data.address.id', $address->id)
            ->assertJsonPath('data.address.primary', 'Av. Lima 123');

        $payload = ['branch_id' => $branch->id, 'name' => 'Tienda 421'];
        $warehouseId = $this->postJson('/test-warehouses', $payload)->assertCreated()
            ->assertJsonPath('data.address', 'Av. Lima 123')
            ->assertJsonPath('data.address_id', $address->id)->json('data.id');

        $this->postJson('/test-warehouses', [...$payload, 'address' => 'Otra dirección'])
            ->assertUnprocessable()->assertJsonValidationErrors('address');
        $this->putJson('/test-warehouses/' . $warehouseId, [...$payload, 'address_id' => $newAddress->id])
            ->assertUnprocessable()->assertJsonValidationErrors('address_id');

        $this->putJson('/test-branches/' . $branch->id, [
            'name' => $branch->name,
            'code' => $branch->code,
            'address_id' => $newAddress->id,
        ])->assertOk();

        $this->getJson('/test-warehouses/' . $warehouseId)->assertOk()
            ->assertJsonPath('data.address', 'Av. Lima 456')
            ->assertJsonPath('data.address_id', $newAddress->id);
        $this->getJson('/test-warehouses?branch_id=' . $branch->id)->assertOk()
            ->assertJsonPath('data.0.address', 'Av. Lima 456');
        $this->assertNull(DB::table('warehouses')->find($warehouseId)->address);
    }

    public function test_creates_a_store_address_and_assigns_it_to_a_new_branch(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('province_id')->nullable();
        });
        DB::table('districts')->insert(['id' => 150115, 'name' => 'La Victoria']);
        Route::post('/test-company-addresses', fn (Request $request) => app(AddressDashboardController::class)->store(Store::findOrFail(1), $request));
        Route::get('/test-company-addresses/{id}', fn (int $id) => app(AddressDashboardController::class)->show(Store::findOrFail(1), $id));
        Route::put('/test-company-addresses/{id}', fn (Request $request, int $id) => app(AddressDashboardController::class)->update($request, Store::findOrFail(1), $id));
        $payload = [
            'addressable_type' => 'store',
            'name' => 'Local Miraflores',
            'primary' => 'Av. Lima 123',
            'district_id' => 150115,
        ];

        $id = $this->postJson('/test-company-addresses', $payload)->assertOk()->json('data.id');
        $address = DB::table('addresses')->find($id);
        $this->assertSame(Store::class, $address->addressable_type);
        $this->assertSame(1, (int) $address->addressable_id);

        $this->getJson('/test-company-addresses/' . $id)->assertOk()
            ->assertJsonPath('data.primary', 'Av. Lima 123');
        $this->putJson('/test-company-addresses/' . $id, [...$payload, 'primary' => 'Av. Lima 456'])
            ->assertOk()->assertJsonPath('data.primary', 'Av. Lima 456');

        $this->postJson('/test-branches', [
            'name' => 'Miraflores',
            'code' => 'MIR-01',
            'address_id' => $id,
        ])->assertCreated()->assertJsonPath('data.address_id', $id);

        $this->postJson('/test-company-addresses', [...$payload, 'addressable_id' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('addressable_id');
        $this->assertSame(1, DB::table('addresses')->count());
        $foreign = Store::findOrFail(2)->addresses()->create([
            'name' => 'Otra empresa', 'primary' => 'Av. Otro 1', 'district_id' => 150115,
        ]);
        $this->getJson('/test-company-addresses/' . $foreign->id)->assertNotFound();
        $this->putJson('/test-company-addresses/' . $foreign->id, $payload)->assertNotFound();
    }

    private function warehouse(Branch $branch): Warehouse
    {
        return Warehouse::create(['store_id' => $branch->store_id, 'branch_id' => $branch->id, 'name' => 'Almacén', 'code' => 'A-'.Warehouse::count()]);
    }

    public function test_creates_complete_hierarchy_and_lists_parent_names(): void
    {
        $branchId = $this->postJson('/test-branches', ['name' => 'Lima', 'code' => 'LIM', 'is_main' => true])->assertCreated()->json('data.id');
        $warehouseId = $this->postJson('/test-warehouses', ['branch_id' => $branchId, 'name' => 'Central', 'code' => 'ALM'])->assertCreated()->json('data.id');
        $parentId = $this->postJson('/test-locations', ['warehouse_id' => $warehouseId, 'name' => 'Zona A'])->assertCreated()->json('data.id');
        $this->postJson('/test-locations', ['warehouse_id' => $warehouseId, 'name' => 'Estante', 'parent_id' => $parentId, 'is_active' => false])->assertCreated()->assertJsonPath('data.is_active', false);
        $this->getJson('/test-branches')->assertOk()->assertJsonPath('data.0.warehouses_count', 1);
        $this->getJson('/test-warehouses?branch_id='.$branchId)->assertOk()->assertJsonPath('data.0.locations_count', 2);
        $this->getJson('/test-locations?warehouse_id='.$warehouseId)->assertOk()->assertJsonPath('data.0.parent.name', 'Zona A');
        $this->getJson('/test-locations?warehouse_id='.$warehouseId.'&all=1')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_codes_are_unique_per_store_and_cannot_override_store_id(): void
    {
        $this->postJson('/test-branches', ['name' => 'Uno', 'code' => 'LIM', 'store_id' => 2])->assertCreated()->assertJsonPath('data.store_id', 1);
        $this->postJson('/test-branches', ['name' => 'Dos', 'code' => 'LIM'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $other = Branch::create(['name' => 'Otra tienda', 'code' => 'LIM', 'store_id' => 2]);
        $this->getJson('/test-branches/'.$other->id)->assertNotFound();
        $this->getJson('/test-branches')->assertJsonCount(1, 'data');
    }

    public function test_main_branch_is_changed_only_within_the_current_store(): void
    {
        $first = $this->branch(); $first->update(['is_main' => true]);
        $other = $this->branch(2); $other->update(['is_main' => true]);
        $this->postJson('/test-branches', ['name' => 'Principal', 'code' => 'MAIN', 'is_main' => true])->assertCreated();
        $this->assertFalse($first->fresh()->is_main);
        $this->assertTrue($other->fresh()->is_main);
    }

    public function test_warehouse_cannot_use_another_stores_branch(): void
    {
        $other = $this->branch(2);
        $this->postJson('/test-warehouses', ['branch_id' => $other->id, 'name' => 'Ajeno', 'code' => 'X'])->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        $this->getJson('/test-warehouses?branch_id='.$other->id)->assertNotFound();
        $warehouse = $this->warehouse($other);
        $this->getJson('/test-warehouses/'.$warehouse->id)->assertNotFound();
        $this->getJson('/test-locations?warehouse_id='.$warehouse->id)->assertNotFound();
    }

    public function test_location_parent_must_belong_to_the_same_warehouse(): void
    {
        $branch = $this->branch();
        $one = $this->warehouse($branch); $two = $this->warehouse($branch);
        $parent = Location::create(['store_id' => 1, 'warehouse_id' => $one->id, 'name' => 'Zona']);
        $this->postJson('/test-locations', ['warehouse_id' => $two->id, 'parent_id' => $parent->id, 'name' => 'Inválida'])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        $foreign = $this->warehouse($this->branch(2));
        $this->postJson('/test-locations', ['warehouse_id' => $foreign->id, 'name' => 'Ajena'])->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
    }

    public function test_branch_address_must_belong_to_the_store(): void
    {
        DB::table('addresses')->insert(['id' => 1, 'addressable_type' => (new Store)->getMorphClass(), 'addressable_id' => 2]);
        $this->postJson('/test-branches', ['name' => 'Sucursal', 'code' => 'S', 'address_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('address_id');
    }

    public function test_pagination_and_validation(): void
    {
        for ($i = 0; $i < 21; $i++) { $this->branch(); }
        $this->getJson('/test-branches')->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('last_page', 2);
        $this->getJson('/test-branches?page=2')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/test-branches', ['name' => '', 'code' => str_repeat('X', 51)])->assertUnprocessable()->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_updates_branch_without_rejecting_its_own_code(): void
    {
        $branch = $this->branch();
        $this->putJson('/test-branches/'.$branch->id, ['name' => 'Actualizada', 'code' => $branch->code, 'is_main' => true])
            ->assertOk()->assertJsonPath('data.name', 'Actualizada')->assertJsonPath('data.is_main', true);
        $other = $this->branch();
        $this->putJson('/test-branches/'.$branch->id, ['name' => 'Repetida', 'code' => $other->code])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame('Actualizada', $branch->fresh()->name);
    }

    public function test_updates_warehouse_and_preserves_store_isolation(): void
    {
        $branch = $this->branch();
        $warehouse = $this->warehouse($branch);
        $this->putJson('/test-warehouses/'.$warehouse->id, ['branch_id' => $branch->id, 'name' => 'Nuevo nombre', 'code' => $warehouse->code])
            ->assertOk()->assertJsonPath('data.name', 'Nuevo nombre');
        $foreign = $this->warehouse($this->branch(2));
        $this->putJson('/test-warehouses/'.$foreign->id, ['branch_id' => $branch->id, 'name' => 'No permitido', 'code' => 'X'])->assertNotFound();
        $this->putJson('/test-branches/'.$foreign->branch_id, ['name' => 'No permitido', 'code' => 'X'])->assertNotFound();
    }

    public function test_location_edit_rejects_self_and_descendants_as_parent(): void
    {
        $warehouse = $this->warehouse($this->branch());
        $root = Location::create(['store_id' => 1, 'warehouse_id' => $warehouse->id, 'name' => 'Zona']);
        $child = Location::create(['store_id' => 1, 'warehouse_id' => $warehouse->id, 'name' => 'Estante', 'parent_id' => $root->id]);
        $payload = ['warehouse_id' => $warehouse->id, 'name' => 'Actualizada', 'parent_id' => $root->id];
        $this->putJson('/test-locations/'.$root->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        $payload['parent_id'] = $child->id;
        $this->putJson('/test-locations/'.$root->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        $payload['parent_id'] = null;
        $this->putJson('/test-locations/'.$child->id, $payload)->assertOk()->assertJsonPath('data.parent_id', null)->assertJsonPath('data.name', 'Actualizada');
    }

    public function test_location_edit_cannot_move_to_another_warehouse(): void
    {
        $branch = $this->branch();
        $one = $this->warehouse($branch); $two = $this->warehouse($branch);
        $location = Location::create(['store_id' => 1, 'warehouse_id' => $one->id, 'name' => 'Zona']);
        $this->putJson('/test-locations/'.$location->id, ['warehouse_id' => $two->id, 'name' => 'Zona'])->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertSame($one->id, $location->fresh()->warehouse_id);
    }

    public function test_deletes_an_unused_location_and_preserves_store_isolation(): void
    {
        $warehouse = $this->warehouse($this->branch());
        $location = Location::create(['store_id' => 1, 'warehouse_id' => $warehouse->id, 'name' => 'Vacía']);
        $this->deleteJson('/test-locations/'.$location->id)->assertNoContent();
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $foreignWarehouse = $this->warehouse($this->branch(2));
        $foreign = Location::create(['store_id' => 2, 'warehouse_id' => $foreignWarehouse->id, 'name' => 'Ajena']);
        $this->deleteJson('/test-locations/'.$foreign->id)->assertNotFound();
        $this->assertDatabaseHas('locations', ['id' => $foreign->id]);
        $this->deleteJson('/test-locations/999999')->assertNotFound();
    }

    public function test_delete_rejects_parents_without_detaching_their_children(): void
    {
        $warehouse = $this->warehouse($this->branch());
        $parent = Location::create(['store_id' => 1, 'warehouse_id' => $warehouse->id, 'name' => 'Zona']);
        $child = Location::create(['store_id' => 1, 'warehouse_id' => $warehouse->id, 'name' => 'Estante', 'parent_id' => $parent->id]);
        $this->deleteJson('/test-locations/'.$parent->id)->assertStatus(409);
        $this->assertSame($parent->id, $child->fresh()->parent_id);
        $this->assertDatabaseHas('locations', ['id' => $parent->id]);
        $this->deleteJson('/test-locations/'.$child->id)->assertNoContent();
        $this->deleteJson('/test-locations/'.$parent->id)->assertNoContent();
    }

    public function test_delete_rejects_inventory_counts_progress_and_scoped_inventory_references(): void
    {
        $warehouse = $this->warehouse($this->branch());
        foreach (['inventory_counts', 'inventory_location_progress', 'inventories'] as $table) {
            $location = Location::create(['store_id' => 1, 'warehouse_id' => $warehouse->id, 'name' => 'Usada '.$table]);
            DB::table($table)->insert($table === 'inventories' ? ['inventoriable_type' => $location->getMorphClass(), 'inventoriable_id' => $location->id] : ['location_id' => $location->id]);
            $this->deleteJson('/test-locations/'.$location->id)->assertStatus(409);
            $this->assertDatabaseHas('locations', ['id' => $location->id]);
        }
    }

    public function test_delete_returns_a_clear_conflict_for_other_foreign_key_references(): void
    {
        Schema::create('location_links', function (Blueprint $table) { $table->id(); $table->foreignId('location_id')->constrained('locations')->restrictOnDelete(); });
        $warehouse = $this->warehouse($this->branch());
        $location = Location::create(['store_id' => 1, 'warehouse_id' => $warehouse->id, 'name' => 'En uso']);
        DB::table('location_links')->insert(['location_id' => $location->id]);
        $this->deleteJson('/test-locations/'.$location->id)->assertStatus(409);
        $this->assertDatabaseHas('locations', ['id' => $location->id]);
        $this->assertDatabaseHas('location_links', ['location_id' => $location->id]);
    }
}
