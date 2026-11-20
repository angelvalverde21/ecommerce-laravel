<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\{CustomerDashboardController, SupplierDashboardController};
use App\Models\{Customer, Store, Supplier, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Route, Schema};
use Tests\TestCase;

class ContactDirectoriesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'contacts_test', 'database.connections.contacts_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('stores', function (Blueprint $t) { $t->id(); $t->string('slug'); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable()->unique();
            $t->string('phone')->nullable(); $t->string('document_number')->nullable();
            $t->unsignedBigInteger('identity_id')->nullable(); $t->string('password')->nullable();
            $t->integer('status')->default(1); $t->timestamps();
        });
        Schema::create('store_user', function (Blueprint $t) { $t->unsignedBigInteger('store_id'); $t->unsignedBigInteger('user_id'); });
        foreach (['customers', 'suppliers'] as $table) {
            Schema::create($table, function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->timestamps(); });
        }
        Schema::create('identities', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('districts', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('addresses', function (Blueprint $t) {
            $t->boolean('is_default')->default(false); $t->integer('status')->default(1);
            $t->string('secondary')->nullable(); $t->string('references')->nullable();
            $t->id(); $t->morphs('addressable'); $t->string('name')->nullable(); $t->string('primary')->nullable();
            $t->unsignedBigInteger('identity_id')->nullable(); $t->unsignedBigInteger('district_id')->nullable();
            $t->string('document_number')->nullable(); $t->string('phone')->nullable(); $t->string('type')->nullable(); $t->timestamps();
        });
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('guard_name')->default('api'); });
        Schema::create('model_has_roles', function (Blueprint $t) { $t->unsignedBigInteger('role_id'); $t->string('model_type'); $t->unsignedBigInteger('model_id'); });
        DB::table('stores')->insert([['id' => 1, 'slug' => 'one'], ['id' => 2, 'slug' => 'two']]);
        DB::table('districts')->insert(['id' => 1, 'name' => 'Miraflores']);
        Route::post('/contact-test/{storeId}/customers', fn (Request $request, $storeId) => app(CustomerDashboardController::class)->store(Store::findOrFail($storeId), $request));
        Route::get('/contact-test/{storeId}/customers', fn ($storeId) => app(CustomerDashboardController::class)->index(Store::findOrFail($storeId)));
        Route::get('/contact-test/{storeId}/customers/{userId}', fn ($storeId, $userId) => app(CustomerDashboardController::class)->show(Store::findOrFail($storeId), $userId));
        Route::put('/contact-test/{storeId}/customers/{userId}', fn (Request $request, $storeId, $userId) => app(CustomerDashboardController::class)->update($request, Store::findOrFail($storeId), $userId));
        Route::post('/contact-test/{storeId}/suppliers', fn (Request $request, $storeId) => app(SupplierDashboardController::class)->store(Store::findOrFail($storeId), $request));
        Route::put('/contact-test/{storeId}/suppliers/{id}', fn (Request $request, $storeId, $id) => app(SupplierDashboardController::class)->update($request, Store::findOrFail($storeId), $id));
    }

    private function user(int $storeId, string $email): User
    {
        $user = User::create(['name' => 'Contacto', 'email' => $email]);
        $user->stores()->attach($storeId);
        return $user;
    }

    public function test_customer_creation_accepts_optional_contact_fields_and_returns_distinct_customer_and_user_ids(): void
    {
        $this->user(2, 'other@example.test');
        $response = $this->postJson('/contact-test/1/customers', ['name' => 'Maria', 'email' => 'maria@example.test']);
        $response->assertOk()->assertJsonPath('data.user.name', 'Maria');
        $this->assertNotEquals($response->json('data.id'), $response->json('data.user_id'));
        $user = User::findOrFail($response->json('data.user_id'));
        $this->assertNull($user->phone);
        $this->assertTrue($user->stores()->where('stores.id', 1)->exists());
        $this->getJson('/contact-test/1/customers/'.$user->id)->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_customer_validation_returns_field_errors_without_creating_partial_records(): void
    {
        $this->postJson('/contact-test/1/customers', ['name' => 'Maria', 'email' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, User::count());
    }

    public function test_customer_list_and_edits_are_scoped_to_the_store_and_require_customer_membership(): void
    {
        $local = $this->user(1, 'local@example.test'); $local->customer()->create();
        $foreign = $this->user(2, 'foreign@example.test'); $foreign->customer()->create();
        $staff = $this->user(1, 'staff@example.test');
        $this->getJson('/contact-test/1/customers')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.user_id', $local->id);
        $this->getJson('/contact-test/1/customers/'.$foreign->id)->assertNotFound();
        $this->getJson('/contact-test/1/customers/'.$staff->id)->assertNotFound();
        $this->putJson('/contact-test/1/customers/'.$foreign->id, ['status' => 0])->assertNotFound();
        $this->putJson('/contact-test/1/customers/'.$local->id, ['name' => 'Actualizado', 'phone' => null])->assertOk();
        $this->assertSame('Actualizado', $local->fresh()->name);
        $this->assertEquals(1, $foreign->fresh()->status);
    }

    public function test_supplier_creation_keeps_optional_identity_fields_empty_and_saves_its_address(): void
    {
        $this->postJson('/contact-test/1/suppliers', [
            'name' => 'Textiles Norte', 'phone' => '999888777', 'email' => 'textiles@example.test',
            'address' => 'Av. Lima 123', 'district_id' => 1,
        ])->assertOk()->assertJsonPath('data.user.email', 'textiles@example.test');
        $supplier = Supplier::firstOrFail();
        $this->assertNull($supplier->user->identity_id);
        $this->assertNull($supplier->user->document_number);
        $this->assertSame('Av. Lima 123', $supplier->addresses()->firstOrFail()->primary);
    }

    public function test_supplier_edits_clear_optional_fields_and_cannot_change_another_stores_supplier(): void
    {
        $user = $this->user(1, 'supplier@example.test');
        $supplier = $user->supplier()->create();
        $this->putJson('/contact-test/2/suppliers/'.$supplier->id, ['name' => 'Wrong store'])->assertNotFound();
        $this->putJson('/contact-test/1/suppliers/'.$supplier->id, [
            'name' => 'Proveedor actualizado', 'email' => null, 'phone' => null, 'identity_id' => null, 'document_number' => null,
        ])->assertOk()->assertJsonPath('data.name', 'Proveedor actualizado');
        $this->assertNull($user->fresh()->email);
        $this->assertNull($user->fresh()->identity_id);
    }

    public function test_supplier_invalid_optional_email_returns_validation_errors(): void
    {
        $this->postJson('/contact-test/1/suppliers', [
            'name' => 'Textiles', 'phone' => '999888777', 'email' => 'invalid', 'address' => 'Lima', 'district_id' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame(0, Supplier::count());
    }

    public function test_supplier_default_address_selection_is_unique_and_validates_ownership(): void
    {
        $supplier = $this->user(1, 'addresses@example.test')->supplier()->create();
        $one = $supplier->addresses()->create(['name' => 'Oficina', 'primary' => 'Lima 123', 'district_id' => 1]);
        $service = app(\App\Services\Dashboard\Supplier\SupplierAddressService::class);
        $service->ensureDefault($supplier);
        $this->assertTrue($one->fresh()->is_default);
        $this->assertEquals($one->id, $service->resolve(Store::find(1), $supplier->id, null));
        $two = $supplier->addresses()->create(['name' => 'Taller', 'primary' => 'Lima 456', 'district_id' => 1]);
        $service->makeDefault($supplier, $two);
        $this->assertFalse($one->fresh()->is_default);
        $this->assertTrue($two->fresh()->is_default);
        $this->assertSame(1, $supplier->addresses()->where('is_default', true)->count());
        $this->assertEquals($two->id, $service->resolve(Store::find(1), $supplier->id, null));
        $this->assertEquals($one->id, $service->resolve(Store::find(1), $supplier->id, $one->id));
        $other = $this->user(1, 'other-address@example.test')->supplier()->create();
        $foreign = $other->addresses()->create(['name' => 'Otra', 'primary' => 'Otra 1', 'district_id' => 1]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->resolve(Store::find(1), $supplier->id, $foreign->id);
    }

    public function test_supplier_creation_accepts_nested_address_and_sets_it_as_default(): void
    {
        $this->postJson('/contact-test/1/suppliers', [
            'name' => 'Proveedor', 'phone' => '999888777',
            'address' => ['primary' => 'Calle 123', 'district_id' => 1, 'secondary' => 'Oficina 2', 'references' => 'Frente al parque'],
        ])->assertOk()->assertJsonPath('data.addresses.0.is_default', true)
            ->assertJsonPath('data.addresses.0.primary', 'Calle 123')
            ->assertJsonPath('data.addresses.0.name', 'Proveedor')
            ->assertJsonPath('data.addresses.0.phone', '999888777')
            ->assertJsonPath('data.addresses.0.secondary', 'Oficina 2')
            ->assertJsonPath('data.addresses.0.references', 'Frente al parque');
    }

    public function test_invalid_address_does_not_leave_a_partial_supplier_or_user(): void
    {
        $this->postJson('/contact-test/1/suppliers', [
            'name' => 'Proveedor', 'phone' => '999888777',
            'address' => ['primary' => 'Calle 123', 'district_id' => 1, 'references' => str_repeat('x', 256)],
        ])->assertUnprocessable()->assertJsonValidationErrors('address.references');
        $this->assertSame(0, Supplier::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, DB::table('addresses')->count());
    }

    public function test_purchase_validation_resolves_the_suppliers_only_address(): void
    {
        $supplier = $this->user(1, 'purchase-address@example.test')->supplier()->create();
        $address = $supplier->addresses()->create(['name' => 'Oficina', 'primary' => 'Lima 123', 'district_id' => 1]);
        $request = Request::create('/', 'POST', [
            'purchaseable_type' => 'manufacture', 'purchaseable_id' => 1, 'supplier_id' => $supplier->id,
        ]);
        $data = app(\App\Services\Dashboard\Purchase\PurchaseService::class)->purchaseValidate(Store::find(1), $request);
        $this->assertEquals($address->id, $data['supplier_address_id']);
    }

    public function test_purchase_orders_persist_and_change_the_selected_supplier_address(): void
    {
        Schema::create('manufactures', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('store_id'); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name'); $table->string('type'); $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('supplier_address_id')->nullable(); $table->date('manufacture_start')->nullable();
            $table->date('manufacture_end')->nullable(); $table->timestamps();
        });
        $supplier = $this->user(1, 'order-address@example.test')->supplier()->create();
        $one = $supplier->addresses()->create(['name' => 'Oficina', 'primary' => 'Lima 123', 'district_id' => 1, 'is_default' => true]);
        $service = app(\App\Services\Dashboard\Crud\ManufactureOrderService::class);
        $payload = ['name' => 'Orden de compra', 'supplier_id' => $supplier->id, 'manufacture_start' => null, 'manufacture_end' => null];
        $order = $service->store(Store::find(1), Request::create('/', 'POST', $payload));
        $this->assertEquals($one->id, $order->fresh()->supplier_address_id);
        $two = $supplier->addresses()->create(['name' => 'Taller', 'primary' => 'Lima 456', 'district_id' => 1]);
        $payload['supplier_address_id'] = $two->id;
        $service->update(Request::create('/', 'PUT', $payload), Store::find(1), $order->id);
        $this->assertEquals($two->id, $order->fresh()->supplier_address_id);
    }
}
