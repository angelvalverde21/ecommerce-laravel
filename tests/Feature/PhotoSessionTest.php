<?php
namespace Tests\Feature;

use App\Models\{PhotoContact, PhotoLocation, PhotoSession, PhotoSessionAsset, User};
use App\Services\PhotoSessions\PhotoSessionStorage;
use Aws\Result;
use Aws\S3\S3Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhotoSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'photos_test', 'database.connections.photos_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true], 'cache.default' => 'array',
            'photo-sessions.bucket' => 'private-photos', 'photo-sessions.preview_bucket' => 'photo-thumbs', 'photo-sessions.part_bytes' => 64 * 1024 * 1024]);
        Schema::create('stores', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('slug'); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('status')->default(true); $t->timestamps(); });
        Schema::create('store_user', function (Blueprint $t) { $t->unsignedBigInteger('store_id'); $t->unsignedBigInteger('user_id'); });
        Schema::create('employees', function (Blueprint $t) { $t->id(); $t->foreignId('store_id')->constrained(); $t->foreignId('user_id')->constrained(); $t->boolean('status')->default(true); });
        Schema::create('identities', fn (Blueprint $t) => $t->id());
        Schema::create('departments', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('provinces', function (Blueprint $t) { $t->id(); $t->string('name'); $t->foreignId('department_id')->constrained(); });
        Schema::create('districts', function (Blueprint $t) { $t->id(); $t->string('name'); $t->foreignId('province_id')->constrained(); });
        DB::table('departments')->insert(['id' => 15, 'name' => 'Lima']);
        DB::table('provinces')->insert(['id' => 1501, 'name' => 'Lima', 'department_id' => 15]);
        DB::table('districts')->insert(['id' => 150122, 'name' => 'Miraflores', 'province_id' => 1501]);
        (require database_path('migrations/2025_08_09_075500_create_addresses_table.php'))->up();
        (require database_path('migrations/2025_11_03_071340_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_11_17_150000_create_photo_sessions_tables.php'))->up();
        (require database_path('migrations/2026_11_17_160000_add_preview_bucket_to_photo_session_assets.php'))->up();
        (require database_path('migrations/2026_11_17_170000_create_photo_contacts_tables.php'))->up();
        (require database_path('migrations/2026_11_17_180000_create_photo_locations_table.php'))->up();
        (require database_path('migrations/2026_11_17_190000_move_photo_location_addresses_to_addresses.php'))->up();
        (require database_path('migrations/2026_11_17_200000_create_photo_session_tasks_tables.php'))->up();
        (require database_path('migrations/2026_11_18_010000_expand_photo_session_assignments.php'))->up();
        (require database_path('migrations/2026_11_18_020000_generalize_tasks_to_polymorphic_tables.php'))->up();
        (require database_path('migrations/2025_08_09_063554_create_images_table.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['ceo', 'marketing', 'sales'] as $role) Role::create(['name' => $role, 'guard_name' => 'api']);
        DB::table('stores')->insert([['id' => 1, 'name' => 'Sorelle', 'slug' => 'sorelle'], ['id' => 2, 'name' => 'Other', 'slug' => 'other']]);
        $user = User::create(['name' => 'Marketing']); $user->assignRole('marketing'); $user->stores()->attach(1);
        $this->actingAs($user->fresh(), 'api');
    }
    protected function tearDown(): void { DB::purge('photos_test'); parent::tearDown(); }
    private function photoSessionRecord(int $store = 1): PhotoSession
    {
        $session = new PhotoSession(['name' => 'Campaña verano', 'status' => 'planned']); $session->store_id = $store; $session->save(); return $session;
    }
    private function asset(PhotoSession $session, array $data = []): PhotoSessionAsset
    {
        return $session->assets()->create([...['id' => (string) Str::uuid(), 'name' => 'original.mov', 'kind' => 'video', 'bytes' => 100,
            'bucket' => 'private-photos', 'object_key' => 'photos/'.Str::uuid(), 'upload_id' => 'pending-upload', 'mime_type' => 'application/octet-stream'], ...$data]);
    }
    private function storage(): \Mockery\MockInterface
    {
        $mock = Mockery::mock(PhotoSessionStorage::class); $this->app->instance(PhotoSessionStorage::class, $mock); return $mock;
    }

    public function test_small_originals_skip_multipart_and_keep_the_existing_upload_contract(): void
    {
        $session = $this->photoSessionRecord();
        $storage = $this->storage();
        $storage->shouldReceive('configured')->andReturnTrue();
        $storage->shouldNotReceive('begin');
        $plan = $this->postJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets',
            ['name' => 'original.jpg', 'bytes' => 300000, 'category' => 'original'])->assertCreated()->assertJsonPath('data.parts', 1)->json('data');
        $asset = PhotoSessionAsset::findOrFail($plan['id']);
        $this->assertNull($asset->upload_id);
        $this->assertSame(300000, $asset->bytes);
        $storage->shouldReceive('singleUrl')->once()->andReturn(['url' => 'https://example.test/signed-original', 'headers' => ['x-amz-meta-asset-id' => $asset->id]]);
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets/'.$asset->id.'/parts/1')->assertOk()
            ->assertJsonPath('data.headers.x-amz-meta-asset-id', $asset->id);
        $this->getJson('/api/v1/other/dashboard/photo-sessions/'.$session->id.'/assets/'.$asset->id.'/parts/1')->assertForbidden();
    }

    public function test_single_upload_verifies_size_and_ownership_metadata_before_accepting_the_original(): void
    {
        $asset = $this->asset($this->photoSessionRecord(), ['upload_id' => null]);
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('headObject')->once()->andReturn(new Result(['ContentLength' => 100, 'Metadata' => ['asset-id' => $asset->id], 'ETag' => 'verified']));
        $client->shouldReceive('headObject')->once()->andReturn(new Result(['ContentLength' => 99, 'Metadata' => ['asset-id' => $asset->id]]));
        $client->shouldNotReceive('listParts'); $client->shouldNotReceive('completeMultipartUpload');
        $storage = Mockery::mock(PhotoSessionStorage::class)->makePartial(); $storage->shouldReceive('client')->andReturn($client);
        $this->assertSame(['etag' => 'verified'], $storage->finish($asset));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $storage->finish($asset);
    }

    public function test_single_upload_rejects_a_different_asset_even_if_the_size_matches(): void
    {
        $asset = $this->asset($this->photoSessionRecord(), ['upload_id' => null]);
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('headObject')->once()->andReturn(new Result(['ContentLength' => 100, 'Metadata' => ['asset-id' => 'other-asset']]));
        $storage = Mockery::mock(PhotoSessionStorage::class)->makePartial(); $storage->shouldReceive('client')->andReturn($client);
        $this->expectException(\Illuminate\Validation\ValidationException::class); $storage->finish($asset);
    }

    public function test_storage_reuses_its_client_and_only_returns_required_upload_headers(): void
    {
        config(['photo-sessions.key' => 'test-key', 'photo-sessions.secret' => 'test-secret',
            'photo-sessions.endpoint' => 'https://s3.us-east-005.backblazeb2.com', 'photo-sessions.region' => 'us-east-005']);
        $storage = new PhotoSessionStorage();
        $this->assertSame($storage->client(), $storage->client());
        $asset = $this->asset($this->photoSessionRecord(), ['upload_id' => null]);
        $plan = $storage->singleUrl($asset);
        $this->assertSame('/private-photos/'.$asset->object_key, parse_url($plan['url'], PHP_URL_PATH));
        foreach (array_keys($plan['headers']) as $name) $this->assertContains(strtolower($name), ['content-type', 'x-amz-meta-asset-id']);
        $this->assertArrayNotHasKey('Authorization', $plan['headers']);
    }

    public function test_preview_batch_is_scoped_and_rejects_duplicate_or_oversized_previews(): void
    {
        $session = $this->photoSessionRecord(); $asset = $this->asset($session, ['status' => 'ready']);
        $url = '/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets/'.$asset->id.'/previews/batch';
        $storage = $this->storage();
        foreach (['thumb', 'medium', 'large'] as $size) $storage->shouldReceive('previewUrl')->once()->withArgs(fn ($record, $variant, $bytes) => $record->id === $asset->id && $variant === $size && $bytes === 100)->andReturn('https://example.test/'.$size);
        $payload = ['previews' => array_map(fn ($size) => ['size' => $size, 'bytes' => 100], ['thumb', 'medium', 'large'])];
        $this->postJson($url, $payload)->assertOk()->assertJsonCount(3, 'data.uploads');
        $this->postJson($url, ['previews' => [['size' => 'thumb', 'bytes' => 100], ['size' => 'thumb', 'bytes' => 100]]])->assertUnprocessable();
        $this->postJson($url, ['previews' => [['size' => 'large', 'bytes' => 10485761]]])->assertUnprocessable();
        $this->postJson(str_replace('/sorelle/', '/other/', $url), $payload)->assertForbidden();
    }

    public function test_preview_verification_launches_all_heads_before_waiting_and_checks_content(): void
    {
        $asset = $this->asset($this->photoSessionRecord()); $started = [];
        $client = Mockery::mock(S3Client::class); $client->shouldNotReceive('doesObjectExistV2');
        $client->shouldReceive('headObjectAsync')->times(3)->andReturnUsing(function ($args) use (&$started) {
            $started[] = $args['Key'];
            $promise = new \GuzzleHttp\Promise\Promise(function () use (&$started, &$promise, $args) {
                $this->assertCount(3, $started);
                $promise->resolve(new Result(['ContentType' => str_contains($args['Key'], '.large.') ? 'text/html' : 'image/webp', 'ContentLength' => 300]));
            });
            return $promise;
        });
        $storage = Mockery::mock(PhotoSessionStorage::class)->makePartial(); $storage->shouldReceive('client')->once()->andReturn($client);
        $this->assertEquals(['thumb' => $asset->object_key.'.thumb.webp', 'medium' => $asset->object_key.'.medium.webp'], $storage->confirmPreviews($asset));
    }

    public function test_preview_verification_does_not_silently_accept_connection_failures(): void
    {
        $asset = $this->asset($this->photoSessionRecord()); $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('headObjectAsync')->times(3)->andReturn(new \GuzzleHttp\Promise\RejectedPromise(new \RuntimeException('Connection unavailable')));
        $storage = Mockery::mock(PhotoSessionStorage::class)->makePartial(); $storage->shouldReceive('client')->andReturn($client);
        $this->expectException(\RuntimeException::class); $storage->confirmPreviews($asset);
    }
    private function contactRecord(string $kind, int $store = 1): PhotoContact
    {
        $contact = new PhotoContact(['kind' => $kind, 'name' => $kind.' de prueba', 'active' => true]);
        $contact->store_id = $store; $contact->save(); return $contact;
    }
    private function expenseSetup(): array
    {
        (require database_path('migrations/2025_08_11_074749_create_gateways_table.php'))->up();
        (require database_path('migrations/2026_02_05_180755_create_payments_table.php'))->up();
        Schema::create('suppliers', function (Blueprint $table) { $table->id(); $table->integer('store_id'); $table->integer('user_id'); $table->string('name')->nullable(); });
        (require database_path('migrations/2026_11_25_100000_create_photo_session_expenses.php'))->up();
        DB::table('gateways')->insert([['id' => 1, 'store_id' => 1, 'name' => 'cash', 'title' => 'Efectivo'], ['id' => 2, 'store_id' => 2, 'name' => 'cash', 'title' => 'Otra tienda']]);
        $session = $this->photoSessionRecord(); $contact = $this->contactRecord('photographer'); $session->photographers()->attach($contact->id);
        $url = '/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/expenses';
        return [$session, $contact, $url, ['source_kind' => 'photographer', 'source_id' => $contact->id, 'request_key' => (string) Str::uuid(),
            'amount' => '200.00', 'date' => now('America/Lima')->toDateString(), 'gateway_id' => 1, 'comment' => 'Adelanto de fotografía', 'direction' => 'out']];
    }
    private function photoPurchaseSetup(): array
    {
        [$session, $contact] = $this->expenseSetup();
        Schema::create('units', function (Blueprint $t) { $t->id(); $t->string('name'); });
        DB::table('units')->insert(['id' => 1, 'name' => 'Unidad']);
        Schema::create('purchases', function (Blueprint $t) { $t->id(); $t->integer('store_id'); $t->integer('user_id')->nullable(); $t->integer('supplier_id')->nullable(); $t->integer('supplier_address_id')->nullable(); $t->morphs('purchaseable'); $t->text('observations')->nullable(); $t->date('purchase_start')->nullable(); $t->date('purchase_end')->nullable(); $t->timestamps(); });
        Schema::create('purchase_items', function (Blueprint $t) { $t->id(); $t->integer('purchase_id'); $t->string('name'); $t->integer('unit_id'); $t->decimal('quantity', 10, 2); $t->decimal('price', 10, 2); $t->decimal('subtotal', 10, 2); $t->timestamps(); });
        Schema::create('attachments', function (Blueprint $t) { $t->id(); $t->morphs('attachable'); });
        (require database_path('migrations/2026_11_13_100000_create_delivery_tracking_tables.php'))->up();
        (require database_path('migrations/2026_11_25_110000_add_photo_source_to_purchases.php'))->up();
        return [$session, $contact, ['purchaseable_type' => 'photo_session', 'purchaseable_id' => $session->id, 'photo_source_kind' => 'photographer', 'photo_source_id' => $contact->id,
            'supplier_id' => null, 'observations' => 'Sesión', 'purchase_items' => [['name' => 'Horas de fotografía', 'unit_id' => 1, 'quantity' => 3, 'price' => 100, 'subtotal' => 999]]]];
    }
    public function test_photo_item_purchases_have_quantity_description_and_price_and_reuse_the_contact(): void
    {
        [$session, $contact, $payload] = $this->photoPurchaseSetup();
        $id = $this->postJson('/api/v1/sorelle/dashboard/purchases', $payload)->assertOk()->assertJsonPath('data.photo_recipient_name', $contact->name)
            ->assertJsonPath('data.items.0.name', 'Horas de fotografía')->assertJsonPath('data.items.0.subtotal', 300)->json('data.id');
        $this->assertDatabaseCount('payments', 0); $this->assertDatabaseCount('suppliers', 0);
        $url = '/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/item-purchases?source_kind=photographer&source_id='.$contact->id;
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'data.purchases')->assertJsonPath('data.total_purchases', 300)->assertJsonPath('data.total_paid', 0);
        $purchase = \App\Models\Purchase::findOrFail($id);
        $purchase->payments()->create(['store_id' => 1, 'user_id' => User::first()->id, 'amount' => 120, 'gateway_id' => 1, 'status' => 'paid', 'direction' => 'out', 'date' => now()]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.total_purchases', 300)->assertJsonPath('data.total_paid', 120);
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id)->assertOk()->assertJsonPath('data.purchase_totals.photographer:'.$contact->id, 300)->assertJsonPath('data.expense_totals.photographer:'.$contact->id, 120);
        $unassigned = $this->contactRecord('photographer');
        $this->postJson('/api/v1/sorelle/dashboard/purchases', [...$payload, 'photo_source_id' => $unassigned->id])->assertNotFound();
        $this->assertDatabaseCount('purchases', 1);
    }
    public function test_task_purchases_require_a_supplier_in_the_session_store(): void
    {
        [$session, $contact, $payload] = $this->photoPurchaseSetup();
        $task = $session->tasks()->create(['store_id' => 1, 'title' => 'Maquillaje', 'created_by' => User::first()->id, 'status' => 'pending']);
        $payload = [...$payload, 'photo_source_kind' => 'task', 'photo_source_id' => $task->id];
        $this->postJson('/api/v1/sorelle/dashboard/purchases', $payload)->assertUnprocessable();
        DB::table('suppliers')->insert(['id' => 1, 'store_id' => 1, 'user_id' => User::first()->id]);
        DB::table('suppliers')->insert(['id' => 2, 'store_id' => 2, 'user_id' => User::first()->id]);
        $this->postJson('/api/v1/sorelle/dashboard/purchases', [...$payload, 'supplier_id' => 2])->assertUnprocessable();
        $this->postJson('/api/v1/sorelle/dashboard/purchases', [...$payload, 'supplier_id' => 1])->assertOk()->assertJsonPath('data.supplier_id', 1);
    }
    public function test_photo_expenses_reuse_the_assigned_contact_and_record_multiple_payments_without_duplicates(): void
    {
        [$session, $contact, $url, $payload] = $this->expenseSetup();
        $this->getJson($url.'?source_kind=photographer&source_id='.$contact->id)->assertOk()->assertJsonPath('data.source.recipient_name', $contact->name)->assertJsonCount(0, 'data.expenses');
        $id = $this->postJson($url, [...$payload, 'recipient_name' => 'No debe reemplazar el contacto'])->assertOk()->assertJsonPath('data.recipient_name', $contact->name)->json('data.payment_id');
        $this->postJson($url, [...$payload, 'recipient_name' => 'No debe reemplazar el contacto'])->assertOk()->assertJsonPath('data.payment_id', $id);
        $this->postJson($url, [...$payload, 'amount' => '201.00'])->assertConflict();
        $this->postJson($url, [...$payload, 'request_key' => (string) Str::uuid(), 'amount' => '300.00'])->assertOk();
        $this->getJson($url.'?source_kind=photographer&source_id='.$contact->id)->assertOk()->assertJsonPath('data.total_paid', 500)->assertJsonCount(2, 'data.expenses');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id)->assertOk()->assertJsonPath('data.expense_totals.photographer:'.$contact->id, 500);
        $this->assertDatabaseCount('payments', 2); $this->assertDatabaseCount('photo_contacts', 1); $this->assertDatabaseCount('suppliers', 0);
        $this->assertDatabaseHas('payments', ['id' => $id, 'paymentable_type' => 'photo_session', 'paymentable_id' => $session->id, 'direction' => 'out', 'status' => 'paid']);
    }
    public function test_photo_expenses_validate_ownership_amount_gateway_and_access(): void
    {
        [$session, $contact, $url, $payload] = $this->expenseSetup();
        foreach ([['amount' => 0], ['amount' => -1], ['amount' => '1.001'], ['gateway_id' => 2], ['comment' => ''], ['direction' => 'in']] as $change) {
            $this->postJson($url, [...$payload, ...$change])->assertUnprocessable();
        }
        $foreign = $this->contactRecord('photographer', 2); $session->photographers()->attach($foreign->id);
        $this->postJson($url, [...$payload, 'source_id' => $foreign->id])->assertNotFound();
        $unassigned = $this->contactRecord('photographer');
        $this->postJson($url, [...$payload, 'source_id' => $unassigned->id])->assertNotFound();
        $this->assertDatabaseCount('payments', 0);
        $user = User::first(); $user->syncRoles(['sales']); $this->actingAs($user->fresh(), 'api');
        $this->postJson($url, $payload)->assertForbidden();
    }
    public function test_task_expenses_require_a_supplier_and_locations_use_their_contact(): void
    {
        [$session, $contact, $url, $payload] = $this->expenseSetup();
        $task = $session->tasks()->create(['store_id' => 1, 'title' => 'Maquillaje', 'created_by' => User::first()->id, 'status' => 'pending']);
        DB::table('suppliers')->insert(['id' => 1, 'store_id' => 1, 'user_id' => User::first()->id]);
        DB::table('suppliers')->insert(['id' => 2, 'store_id' => 2, 'user_id' => User::first()->id]);
        $taskPayload = [...$payload, 'source_kind' => 'task', 'source_id' => $task->id];
        $this->postJson($url, $taskPayload)->assertUnprocessable();
        $this->postJson($url, [...$taskPayload, 'supplier_id' => 2])->assertUnprocessable();
        $this->postJson($url, [...$taskPayload, 'supplier_id' => 1])->assertOk()->assertJsonPath('data.supplier_id', 1);
        $location = new PhotoLocation(['name' => 'Estudio', 'type' => 'studio', 'contact_name' => 'Dueña del estudio', 'active' => true]); $location->store_id = 1; $location->save(); $session->locations()->attach($location->id);
        $this->postJson($url, [...$payload, 'request_key' => (string) Str::uuid(), 'source_kind' => 'location', 'source_id' => $location->id])->assertOk()->assertJsonPath('data.recipient_name', 'Dueña del estudio');
    }
    public function test_internal_responsible_options_and_validation_only_allow_active_store_employees(): void
    {
        $employee = User::create(['name' => 'Empleado activo']); $employee->stores()->attach(1);
        $external = User::create(['name' => 'Usuario sin ficha de empleado']); $external->stores()->attach(1);
        $foreign = User::create(['name' => 'Empleado de otra tienda']);
        $inactive = User::create(['name' => 'Empleado inactivo']);
        $blocked = User::create(['name' => 'Cuenta deshabilitada', 'status' => false]);
        DB::table('employees')->insert([
            ['id' => 90, 'store_id' => 1, 'user_id' => $employee->id, 'status' => true],
            ['id' => 91, 'store_id' => 2, 'user_id' => $foreign->id, 'status' => true],
            ['id' => 92, 'store_id' => 1, 'user_id' => $inactive->id, 'status' => false],
            ['id' => 93, 'store_id' => 1, 'user_id' => $blocked->id, 'status' => true],
        ]);
        $this->storage()->shouldReceive('configured')->andReturnFalse();
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/options')->assertOk()->assertJsonCount(1, 'data.employees')
            ->assertJsonPath('data.employees.0.id', $employee->id)->assertJsonPath('data.employees.0.employee_id', 90)->assertJsonMissingPath('data.users');
        $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Sesión', 'status' => 'planned', 'responsible_id' => $employee->id])
            ->assertCreated()->assertJsonPath('data.responsible.name', 'Empleado activo');
        foreach ([$external, $foreign, $inactive, $blocked] as $invalid) {
            $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Sesión', 'status' => 'planned', 'responsible_id' => $invalid->id])
                ->assertUnprocessable()->assertJsonValidationErrors(['responsible_id']);
        }
    }
    public function test_location_address_is_polymorphic_atomic_and_keeps_one_record_on_updates(): void
    {
        $base = '/api/v1/sorelle/dashboard/photo-sessions/locations';
        $values = ['name' => 'Estudio con dirección', 'type' => 'studio', 'rate_unit' => 'hour', 'active' => true];
        $result = $this->postJson($base, [...$values, 'address' => ['primary' => 'Av. de prueba 123', 'secondary' => 'Piso 2', 'district_id' => 150122, 'url_maps' => 'https://example.test/mapa', 'latitud' => 0, 'longitud' => -77]])
            ->assertCreated()->assertJsonPath('data.address.district.province.department.name', 'Lima')
            ->assertJsonPath('data.display_address', 'Av. de prueba 123 · Piso 2')->assertJsonPath('data.district_name', 'Miraflores');
        $id = $result->json('data.id'); $address = DB::table('addresses')->first();
        $this->assertSame(PhotoLocation::class, $address->addressable_type); $this->assertSame($id, (int) $address->addressable_id);
        $this->putJson($base.'/'.$id, [...$values, 'address' => ['primary' => 'Dirección actualizada', 'district_id' => 150122]])->assertOk()->assertJsonPath('data.address.id', $address->id);
        $this->assertSame(1, DB::table('addresses')->count());
        $this->putJson($base.'/'.$id, [...$values, 'address' => ['primary' => 'Inválida', 'district_id' => 999999]])->assertUnprocessable();
        $this->assertSame('Dirección actualizada', DB::table('addresses')->value('primary'));
        $this->putJson($base.'/'.$id, [...$values, 'name' => 'Nuevo nombre'])->assertOk()->assertJsonPath('data.address.primary', 'Dirección actualizada');
        $this->assertSame('Nuevo nombre', DB::table('addresses')->value('name'));
        $this->putJson($base.'/'.$id, [...$values, 'address' => null])->assertOk()->assertJsonPath('data.address', null);
        $this->assertSame(0, DB::table('addresses')->count());
        $this->postJson($base, [...$values, 'address' => ['district_id' => 999999]])->assertUnprocessable();
        $this->assertSame(1, PhotoLocation::count());
    }
    public function test_shared_address_api_supports_photo_locations_and_rejects_duplicates_and_foreign_owners(): void
    {
        $location = new PhotoLocation(['name' => 'Estudio', 'type' => 'studio', 'active' => true]); $location->store_id = 1; $location->save();
        $other = new PhotoLocation(['name' => 'Otra locación', 'type' => 'cafe', 'active' => true]); $other->store_id = 1; $other->save();
        $foreign = new PhotoLocation(['name' => 'Ajena', 'type' => 'cafe', 'active' => true]); $foreign->store_id = 2; $foreign->save();
        $data = ['addressable_type' => 'photo_location', 'addressable_id' => $location->id, 'primary' => 'Av. compartida', 'district_id' => 150122];
        $result = $this->postJson('/api/v1/sorelle/dashboard/addresses', $data)->assertOk(); $id = $result->json('data.id');
        $this->postJson('/api/v1/sorelle/dashboard/addresses', $data)->assertUnprocessable();
        $this->putJson('/api/v1/sorelle/dashboard/addresses/'.$id, [...$data, 'primary' => 'Actualizada'])->assertOk()->assertJsonPath('data.primary', 'Actualizada');
        $this->getJson('/api/v1/sorelle/dashboard/addresses/'.$id.'?addressable_type=photo_location&addressable_id='.$location->id)->assertOk();
        $this->putJson('/api/v1/sorelle/dashboard/addresses/'.$id, [...$data, 'addressable_id' => $other->id])->assertNotFound();
        $this->postJson('/api/v1/sorelle/dashboard/addresses', [...$data, 'addressable_id' => $foreign->id])->assertNotFound();
        $user = User::first(); $user->syncRoles(['sales']); $this->actingAs($user->fresh(), 'api');
        $this->putJson('/api/v1/sorelle/dashboard/addresses/'.$id, $data)->assertForbidden();
        $this->assertSame(1, DB::table('addresses')->count());
    }
    public function test_existing_location_addresses_migrate_without_guessing_ambiguous_districts_or_truncating_text(): void
    {
        $migration = require database_path('migrations/2026_11_17_190000_move_photo_location_addresses_to_addresses.php');
        $migration->down();
        DB::table('districts')->insert([['id' => 150101, 'name' => 'Centro', 'province_id' => 1501], ['id' => 150102, 'name' => 'Centro', 'province_id' => 1501]]);
        $long = str_repeat('a', 500);
        foreach ([['name' => 'Larga', 'address' => $long, 'district' => 'miraflores', 'city' => 'Lima'], ['name' => 'Ambigua', 'address' => 'Calle 2', 'district' => 'Centro', 'city' => null], ['name' => 'Sin distrito', 'address' => null, 'district' => 'Zona desconocida', 'city' => 'Ciudad anterior']] as $values) {
            DB::table('photo_locations')->insert([...$values, 'store_id' => 1, 'type' => 'studio', 'active' => true]);
        }
        $migration->up();
        $this->assertFalse(Schema::hasColumn('photo_locations', 'address'));
        $this->assertSame(3, DB::table('addresses')->where('addressable_type', PhotoLocation::class)->count());
        $this->assertSame($long, DB::table('addresses')->where('name', 'Larga')->value('primary'));
        $this->assertSame(150122, (int) DB::table('addresses')->where('name', 'Larga')->value('district_id'));
        $this->assertNull(DB::table('addresses')->where('name', 'Ambigua')->value('district_id'));
        $this->assertSame('Distrito/zona anterior: Centro', DB::table('addresses')->where('name', 'Ambigua')->value('references'));
        $this->assertSame('Ciudad anterior', DB::table('addresses')->where('name', 'Sin distrito')->value('city'));
        $migration->down(); $migration->up();
        $this->assertSame(3, DB::table('addresses')->count());
    }
    public function test_photo_locations_create_edit_filter_and_keep_store_isolation(): void
    {
        $base = '/api/v1/sorelle/dashboard/photo-sessions/locations';
        $response = $this->postJson($base, ['name' => 'Café de prueba', 'type' => 'cafe', 'address' => ['primary' => 'Dirección de prueba', 'district_id' => 150122], 'rate' => 120, 'rate_unit' => 'hour', 'active' => true])->assertCreated();
        $id = $response->json('data.id');
        $foreign = new PhotoLocation(['name' => 'Otro estudio', 'type' => 'studio', 'rate_unit' => 'day', 'active' => true]); $foreign->store_id = 2; $foreign->save();
        $this->getJson($base.'?type=cafe&q=Miraflores')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.sessions_count', 0);
        $this->getJson($base.'/'.$foreign->id)->assertNotFound();
        $this->getJson('/api/v1/other/dashboard/photo-sessions/locations')->assertForbidden();
        $this->putJson($base.'/'.$id, ['name' => 'Café actualizado', 'type' => 'cafe', 'rate_unit' => 'session', 'active' => false])->assertOk()->assertJsonPath('data.active', false);
        $this->getJson($base.'?active=1')->assertOk()->assertJsonPath('total', 0);
        $this->getJson($base.'?active=0')->assertOk()->assertJsonPath('total', 1);
    }
    public function test_photo_location_validates_rates_type_and_external_links(): void
    {
        $this->postJson('/api/v1/sorelle/dashboard/photo-sessions/locations', ['name' => 'Locación inválida', 'type' => 'warehouse', 'rate' => -1, 'rate_unit' => 'unknown', 'reference_url' => 'javascript:alert(1)', 'email' => 'invalid', 'active' => true])
            ->assertUnprocessable()->assertJsonValidationErrors(['type', 'rate', 'rate_unit', 'reference_url', 'email']);
        $this->assertSame(0, PhotoLocation::count());
    }
    public function test_session_assigns_a_photo_location_preserves_legacy_text_and_rejects_foreign_venues(): void
    {
        $venue = new PhotoLocation(['name' => 'Airbnb de prueba', 'type' => 'airbnb', 'active' => true]); $venue->store_id = 1; $venue->save();
        $venue->address()->create(['name' => $venue->name, 'primary' => 'Dirección de prueba']);
        $foreign = new PhotoLocation(['name' => 'Ajena', 'type' => 'studio', 'active' => true]); $foreign->store_id = 2; $foreign->save();
        $response = $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Sesión con locación', 'status' => 'planned', 'photo_location_id' => $venue->id, 'location' => 'Entrada lateral'])->assertCreated()
            ->assertJsonPath('data.photo_location.id', $venue->id)->assertJsonPath('data.location', 'Entrada lateral');
        $id = $response->json('data.id'); $venue->update(['active' => false]);
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$id)->assertOk()->assertJsonPath('data.photo_location.name', 'Airbnb de prueba');
        $this->putJson('/api/v1/sorelle/dashboard/photo-sessions/'.$id, ['name' => 'Actualizada', 'status' => 'planned'])->assertOk()->assertJsonPath('data.photo_location_id', $venue->id);
        $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Ajena', 'status' => 'planned', 'photo_location_id' => $foreign->id])->assertUnprocessable()->assertJsonValidationErrors(['photo_location_id']);
        $this->storage()->shouldReceive('configured')->andReturnFalse();
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/options')->assertOk()->assertJsonCount(1, 'data.locations')->assertJsonPath('data.locations.0.active', false);
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions')->assertOk()->assertJsonPath('data.0.photo_location.name', 'Airbnb de prueba');
    }
    public function test_model_reference_gallery_uses_images_and_saves_three_variants_without_altering_original(): void
    {
        Storage::fake('public');
        $model = $this->contactRecord('model');
        $file = UploadedFile::fake()->image('reference.jpg', 1400, 900);
        $bytes = file_get_contents($file->getRealPath());
        $response = $this->post('/api/v1/sorelle/dashboard/images', ['imageable_type' => 'photo_model', 'imageable_id' => $model->id, 'images' => [$file]], ['Accept' => 'application/json'])->assertOk()->assertJsonCount(1, 'data');
        $image = $model->images()->firstOrFail();
        foreach (['thumbnail', 'medium', 'large'] as $variant) {
            Storage::disk('public')->assertExists('images/'.$image->{$variant});
            $this->assertNotEmpty($response->json('data.0.url_'.$variant));
        }
        $this->assertSame($bytes, Storage::disk('public')->get('images/'.$image->large));
        $this->assertSame(360, getimagesize(Storage::disk('public')->path('images/'.$image->thumbnail))[0]);
        $this->assertSame(960, getimagesize(Storage::disk('public')->path('images/'.$image->medium))[0]);
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/contacts/'.$model->id.'?kind=model')->assertOk()->assertJsonCount(1, 'data.images');
        $this->putJson('/api/v1/sorelle/dashboard/photo-sessions/contacts/'.$model->id, ['kind' => 'model', 'name' => 'Nombre actualizado', 'active' => true])->assertOk()->assertJsonCount(1, 'data.images');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/contacts?kind=model')->assertOk()->assertJsonPath('data.0.images_count', 1);
        $other = $this->contactRecord('model');
        $this->deleteJson('/api/v1/sorelle/dashboard/images/'.$image->id, ['imageable_type' => 'photo_model', 'imageable_id' => $other->id])->assertNotFound();
        Storage::disk('public')->assertExists('images/'.$image->large);
        $this->deleteJson('/api/v1/sorelle/dashboard/images/'.$image->id, ['imageable_type' => 'photo_model', 'imageable_id' => $model->id])->assertOk();
        foreach (['thumbnail', 'medium', 'large'] as $variant) Storage::disk('public')->assertMissing('images/'.$image->{$variant});
        $this->assertSame(0, $model->images()->count());
    }
    public function test_model_reference_upload_enforces_model_kind_store_role_and_image_format(): void
    {
        Storage::fake('public');
        $model = $this->contactRecord('model'); $foreign = $this->contactRecord('model', 2); $photographer = $this->contactRecord('photographer');
        foreach ([$foreign, $photographer] as $contact) {
            $this->post('/api/v1/sorelle/dashboard/images', ['imageable_type' => 'photo_model', 'imageable_id' => $contact->id, 'images' => [UploadedFile::fake()->image('reference.jpg')]], ['Accept' => 'application/json'])->assertNotFound();
        }
        $this->post('/api/v1/sorelle/dashboard/images', ['imageable_type' => 'photo_model', 'imageable_id' => $model->id, 'images' => [UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')]], ['Accept' => 'application/json'])->assertUnprocessable();
        $user = User::first(); $user->syncRoles(['sales']); $this->actingAs($user->fresh(), 'api');
        $this->post('/api/v1/sorelle/dashboard/images', ['imageable_type' => 'photo_model', 'imageable_id' => $model->id, 'images' => [UploadedFile::fake()->image('reference.jpg')]], ['Accept' => 'application/json'])->assertForbidden();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
    public function test_photographer_and_model_directories_create_edit_filter_and_isolate_store_and_kind(): void
    {
        $base = '/api/v1/sorelle/dashboard/photo-sessions/contacts';
        $response = $this->postJson($base, ['kind' => 'photographer', 'name' => 'Fotógrafo catálogo', 'phone' => '999111222', 'active' => true])->assertCreated();
        $id = $response->json('data.id'); $this->contactRecord('model'); $foreign = $this->contactRecord('photographer', 2);
        $this->getJson($base.'?kind=photographer')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($base.'?kind=model')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($base.'/'.$id.'?kind=model')->assertNotFound();
        $this->getJson($base.'/'.$foreign->id.'?kind=photographer')->assertNotFound();
        $this->putJson($base.'/'.$id, ['kind' => 'photographer', 'name' => 'Fotógrafo actualizado', 'active' => false])->assertOk()->assertJsonPath('data.active', false);
        $this->getJson($base.'?kind=photographer&active=1')->assertOk()->assertJsonPath('total', 0);
        $this->getJson($base.'?kind=photographer&active=0&q=actualizado')->assertOk()->assertJsonPath('total', 1);
        $this->postJson($base, ['kind' => 'model', 'name' => 'Modelo', 'active' => true, 'portfolio_url' => 'javascript:alert(1)', 'email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['portfolio_url', 'email']);
        $this->getJson($base.'?kind=unknown')->assertUnprocessable();
    }
    public function test_session_can_assign_a_photographer_and_multiple_models_without_losing_legacy_text(): void
    {
        $photographer = $this->contactRecord('photographer'); $model = $this->contactRecord('model'); $second = $this->contactRecord('model');
        $response = $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Con equipo', 'status' => 'planned',
            'photographer_contact_id' => $photographer->id, 'photographer' => 'Referencia anterior', 'model_ids' => [$model->id, $second->id]])->assertCreated()
            ->assertJsonPath('data.photographer_contact.id', $photographer->id)->assertJsonCount(2, 'data.models');
        $id = $response->json('data.id');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$id)->assertOk()->assertJsonPath('data.model_ids', [$model->id, $second->id])->assertJsonPath('data.photographer', 'Referencia anterior');
        $this->putJson('/api/v1/sorelle/dashboard/photo-sessions/'.$id, ['name' => 'Solo cambia nombre', 'status' => 'planned'])->assertOk()->assertJsonCount(2, 'data.models');
        $this->putJson('/api/v1/sorelle/dashboard/photo-sessions/'.$id, ['name' => 'Sin modelos', 'status' => 'planned', 'model_ids' => []])->assertOk()->assertJsonCount(0, 'data.models');
        $this->contactRecord('model', 2);
        $this->storage()->shouldReceive('configured')->andReturnFalse();
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/options')->assertOk()->assertJsonCount(1, 'data.photographers')->assertJsonCount(2, 'data.models');
    }
    public function test_session_rejects_contacts_from_another_store_wrong_kind_and_duplicate_models(): void
    {
        $photographer = $this->contactRecord('photographer'); $model = $this->contactRecord('model'); $foreign = $this->contactRecord('model', 2);
        $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Equipo inválido', 'status' => 'planned', 'photographer_contact_id' => $model->id,
            'model_ids' => [$photographer->id, $foreign->id, $model->id, $model->id]])->assertUnprocessable()
            ->assertJsonValidationErrors(['photographer_contact_id', 'model_ids.0', 'model_ids.1', 'model_ids.2', 'model_ids.3']);
        $this->assertSame(0, PhotoSession::count());
    }
    private function taskEmployee(string $name, int $store = 1, bool $active = true): User
    {
        $user = User::create(['name' => $name]); $user->assignRole('sales'); $user->stores()->attach($store);
        DB::table('employees')->insert(['store_id' => $store, 'user_id' => $user->id, 'status' => $active]);
        return $user->fresh();
    }
    private function taskCeo(): User
    {
        $user = User::create(['name' => 'CEO']); $user->assignRole('ceo'); $user->stores()->attach(1);
        $this->actingAs($user->fresh(), 'api'); return $user;
    }
    public function test_ceo_assigns_session_tasks_and_employee_updates_only_their_own_tasks_with_audit(): void
    {
        $ceo = $this->taskCeo(); $employee = $this->taskEmployee('Responsable'); $other = $this->taskEmployee('Otro empleado');
        $session = $this->photoSessionRecord(); $base = '/api/v1/sorelle/dashboard/photo-session-tasks';
        $first = $this->postJson($base, ['photo_session_id' => $session->id, 'title' => 'Alistar prendas', 'assigned_to' => $employee->id, 'due_date' => now('America/Lima')->subDay()->toDateString()])->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.version', 1)->assertJsonPath('data.overdue', true)->json('data.id');
        $second = $this->postJson($base, ['photo_session_id' => $session->id, 'title' => 'Accesorios', 'assigned_to' => $other->id])->assertCreated()->json('data.id');
        $this->getJson($base.'?photo_session_id='.$session->id)->assertOk()->assertJsonPath('summary.total', 2)->assertJsonPath('summary.overdue', 1)->assertJsonPath('can_manage', true)->assertJsonCount(2, 'assignees');
        $this->actingAs($employee, 'api');
        $this->getJson($base.'?photo_session_id='.$session->id)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('can_manage', false)->assertJsonCount(0, 'assignees')->assertJsonPath('data.0.can_update', true);
        $unassignedSession = $this->photoSessionRecord();
        $unassignedSession->update(['name' => 'Sin asignaciones']);
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('can_create', false)->assertJsonPath('data.0.can_open_workspace', false)->assertJsonMissingPath('data.0.budget');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/options')->assertForbidden();
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id)->assertForbidden();
        $this->getJson($base.'?photo_session_id='.$unassignedSession->id)->assertForbidden();
        $this->postJson($base, ['photo_session_id' => $session->id, 'title' => 'No permitido', 'assigned_to' => $employee->id])->assertForbidden();
        $this->putJson($base.'/'.$first, ['title' => 'Reasignar', 'assigned_to' => $other->id, 'version' => 1])->assertForbidden();
        $this->putJson($base.'/'.$second.'/status', ['status' => 'completed', 'version' => 1])->assertForbidden();
        $this->getJson($base.'/'.$second.'/events')->assertForbidden();
        $this->putJson($base.'/'.$first.'/status', ['status' => 'blocked', 'version' => 1])->assertUnprocessable();
        $this->putJson($base.'/'.$first.'/status', ['status' => 'blocked', 'version' => 1, 'note' => 'Faltan prendas'])->assertOk()->assertJsonPath('data.last_note', 'Faltan prendas');
        $this->putJson($base.'/'.$first.'/status', ['status' => 'completed', 'version' => 2, 'note' => 'Todo listo'])->assertOk()->assertJsonPath('data.version', 3)->assertJsonPath('data.overdue', false)->assertJsonPath('data.completed_by', $employee->id)->assertJsonPath('data.status', 'completed');
        $this->getJson($base.'/'.$first.'/events')->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.0.actor.name', 'Responsable')->assertJsonPath('data.0.changes.before.status', 'blocked')->assertJsonPath('data.0.changes.after.status', 'completed');
        $this->actingAs($ceo->fresh(), 'api');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions?q='.urlencode($session->name))->assertOk()->assertJsonPath('data.0.tasks_count', 2)->assertJsonPath('data.0.completed_tasks_count', 1)->assertJsonPath('data.0.overdue_tasks_count', 0);
    }
    public function test_task_assignments_require_active_store_employees_and_session_scope(): void
    {
        $this->taskCeo(); $session = $this->photoSessionRecord(); $foreignSession = $this->photoSessionRecord(2);
        $foreign = $this->taskEmployee('Ajeno', 2); $inactive = $this->taskEmployee('Inactivo', 1, false);
        $base = '/api/v1/sorelle/dashboard/photo-session-tasks';
        foreach ([$foreign, $inactive] as $invalid) $this->postJson($base, ['photo_session_id' => $session->id, 'title' => 'Prendas', 'assigned_to' => $invalid->id])->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
        $employee = $this->taskEmployee('Activo');
        $this->postJson($base, ['photo_session_id' => $foreignSession->id, 'title' => 'Ajena', 'assigned_to' => $employee->id])->assertUnprocessable();
        $this->getJson($base.'?photo_session_id='.$foreignSession->id)->assertNotFound();
        $foreignTask = \App\Models\Task::create(['store_id' => 2, 'taskable_type' => 'photo_session', 'taskable_id' => $foreignSession->id, 'title' => 'Tarea ajena', 'assigned_to' => $employee->id]);
        $this->putJson($base.'/'.$foreignTask->id.'/status', ['status' => 'completed', 'version' => 1])->assertNotFound();
        $this->getJson($base.'/'.$foreignTask->id.'/events')->assertNotFound();
    }
    public function test_task_conflicts_preserve_audit_and_reopening_clears_completion(): void
    {
        $this->taskCeo(); $session = $this->photoSessionRecord(); $employee = $this->taskEmployee('Responsable'); $other = $this->taskEmployee('Reasignado');
        $base = '/api/v1/sorelle/dashboard/photo-session-tasks';
        $id = $this->postJson($base, ['photo_session_id' => $session->id, 'title' => 'Prendas', 'assigned_to' => $employee->id])->assertCreated()->json('data.id');
        $this->putJson($base.'/'.$id, ['title' => 'Prendas listas', 'assigned_to' => $other->id, 'version' => 1])->assertOk()->assertJsonPath('data.version', 2);
        $this->putJson($base.'/'.$id.'/status', ['status' => 'completed', 'version' => 1])->assertConflict();
        $this->putJson($base.'/'.$id.'/status', ['status' => 'completed', 'version' => 2])->assertOk()->assertJsonPath('data.status', 'completed');
        $this->putJson($base.'/'.$id.'/status', ['status' => 'in_progress', 'version' => 3])->assertOk()->assertJsonPath('data.completed_at', null)->assertJsonPath('data.completed_by', null);
        $this->getJson($base.'/'.$id.'/events')->assertOk()->assertJsonCount(4, 'data')->assertJsonPath('data.2.changes.before.assigned_to', $employee->id)->assertJsonPath('data.2.changes.after.assigned_to', $other->id);
        DB::table('employees')->where('user_id', $other->id)->update(['status' => false]);
        $this->actingAs($other, 'api');
        $this->getJson($base.'?photo_session_id='.$session->id)->assertForbidden();
        $this->putJson($base.'/'.$id.'/status', ['status' => 'completed', 'version' => 4])->assertForbidden();
    }
    public function test_sessions_allow_multiple_photographers_and_locations_with_scoped_atomic_assignments(): void
    {
        $first = $this->contactRecord('photographer'); $second = $this->contactRecord('photographer'); $model = $this->contactRecord('model'); $foreign = $this->contactRecord('photographer', 2);
        $venue = new PhotoLocation(['name' => 'Estudio', 'type' => 'studio', 'active' => true]); $venue->store_id = 1; $venue->save();
        $otherVenue = new PhotoLocation(['name' => 'Café', 'type' => 'cafe', 'active' => true]); $otherVenue->store_id = 1; $otherVenue->save();
        $base = '/api/v1/sorelle/dashboard/photo-sessions';
        $created = $this->postJson($base, ['name' => 'Sesión múltiple', 'photographer_ids' => [$first->id, $second->id], 'location_ids' => [$venue->id, $otherVenue->id], 'model_ids' => [$model->id]])
            ->assertCreated()->assertJsonCount(2, 'data.photographers')->assertJsonCount(2, 'data.locations')->assertJsonPath('data.photographer_ids', [$first->id, $second->id]);
        $id = $created->json('data.id');
        $this->putJson($base.'/'.$id.'/assignments', ['kind' => 'photographer', 'mode' => 'attach', 'ids' => [$first->id]])->assertOk()->assertJsonCount(2, 'data.photographers');
        $this->putJson($base.'/'.$id.'/assignments', ['kind' => 'photographer', 'mode' => 'attach', 'ids' => [$model->id]])->assertUnprocessable();
        $this->putJson($base.'/'.$id.'/assignments', ['kind' => 'photographer', 'mode' => 'attach', 'ids' => [$foreign->id]])->assertUnprocessable();
        $this->putJson($base.'/'.$id.'/assignments', ['kind' => 'location', 'mode' => 'detach', 'ids' => [$venue->id]])->assertOk()->assertJsonCount(1, 'data.locations')->assertJsonPath('data.photo_location_id', $otherVenue->id);
        $this->putJson($base.'/'.$id, ['name' => 'Cambio general', 'status' => 'planned'])->assertOk()->assertJsonCount(2, 'data.photographers')->assertJsonCount(1, 'data.locations');
        $this->putJson($base.'/'.$id.'/assignments', ['kind' => 'model', 'mode' => 'detach', 'ids' => [$model->id]])->assertOk()->assertJsonCount(0, 'data.models');
        $this->assertDatabaseHas('photo_contacts', ['id' => $model->id]);
    }
    public function test_assignment_migration_preserves_existing_singular_links(): void
    {
        $migration = require database_path('migrations/2026_11_18_010000_expand_photo_session_assignments.php');
        $migration->down();
        $photographer = $this->contactRecord('photographer');
        $location = new PhotoLocation(['name' => 'Anterior', 'type' => 'studio', 'active' => true]); $location->store_id = 1; $location->save();
        $session = $this->photoSessionRecord(); $session->update(['photographer_contact_id' => $photographer->id, 'photo_location_id' => $location->id]);
        $migration->up();
        $this->assertDatabaseHas('photo_session_photographers', ['photo_session_id' => $session->id, 'photo_contact_id' => $photographer->id]);
        $this->assertDatabaseHas('photo_session_locations', ['photo_session_id' => $session->id, 'photo_location_id' => $location->id]);
    }
    public function test_polymorphic_task_migration_keeps_ids_history_and_status_and_isolates_other_modules(): void
    {
        $migration = require database_path('migrations/2026_11_18_020000_generalize_tasks_to_polymorphic_tables.php');
        $migration->down();
        $session = $this->photoSessionRecord(); $actor = auth('api')->user();
        DB::table('photo_session_tasks')->insert(['id' => 81, 'photo_session_id' => $session->id, 'title' => 'Prendas listas', 'status' => 'completed', 'assigned_to' => $actor->id, 'created_by' => $actor->id, 'completed_by' => $actor->id, 'version' => 3, 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('photo_session_task_events')->insert(['id' => 91, 'photo_session_task_id' => 81, 'actor_id' => $actor->id, 'type' => 'status_changed', 'changes' => json_encode(['after' => ['status' => 'completed']]), 'created_at' => now()]);
        $migration->up();
        $this->assertFalse(Schema::hasTable('photo_session_tasks'));
        $this->assertDatabaseHas('tasks', ['id' => 81, 'store_id' => 1, 'taskable_type' => 'photo_session', 'taskable_id' => $session->id, 'version' => 3, 'status' => 'completed']);
        $this->assertDatabaseHas('task_events', ['id' => 91, 'task_id' => 81, 'actor_id' => $actor->id]);
        $task = \App\Models\Task::findOrFail(81); $this->assertTrue($task->taskable->is($session));
        $unrelated = \App\Models\Task::create(['store_id' => 1, 'taskable_type' => \App\Models\Store::class, 'taskable_id' => 1, 'title' => 'Tarea de otro módulo']);
        $this->assertInstanceOf(\App\Models\Store::class, $unrelated->taskable);
        $this->getJson('/api/v1/sorelle/dashboard/photo-session-tasks')->assertUnprocessable()->assertJsonValidationErrors('photo_session_id');
        $this->getJson('/api/v1/sorelle/dashboard/photo-session-tasks?photo_session_id='.$session->id)->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/sorelle/dashboard/photo-session-tasks/'.$unrelated->id.'/events')->assertNotFound();
    }
    public function test_new_sessions_default_to_preparation_and_status_can_change_after_creation(): void
    {
        $base = '/api/v1/sorelle/dashboard/photo-sessions';
        $this->postJson($base, ['name' => 'Estado automático'])->assertCreated()->assertJsonPath('data.status', 'planned');
        $created = $this->postJson($base, ['name' => 'Inicia en preparación', 'status' => 'completed'])
            ->assertCreated()->assertJsonPath('data.status', 'planned');
        $this->putJson($base.'/'.$created->json('data.id'), ['name' => 'Sesión en curso', 'status' => 'in_progress'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');
    }

    public function test_sessions_create_edit_filter_and_keep_store_and_role_isolation(): void
    {
        $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Sesión catálogo', 'status' => 'planned', 'shoot_date' => '2026-12-01', 'delivery_date' => '2026-12-05', 'team' => 'Modelo y maquillaje', 'budget' => 800])
            ->assertCreated()->assertJsonPath('data.name', 'Sesión catálogo');
        $id = PhotoSession::first()->id;
        $this->putJson('/api/v1/sorelle/dashboard/photo-sessions/'.$id, ['name' => 'Sesión actualizada', 'status' => 'editing'])->assertOk()->assertJsonPath('data.status', 'editing');
        $this->photoSessionRecord(2);
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions?status=editing&q=actualizada')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/other/dashboard/photo-sessions/'.$id)->assertForbidden();
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$this->photoSessionRecord(2)->id)->assertNotFound();
        $user = User::first(); $user->syncRoles(['sales']); $this->actingAs($user->fresh(), 'api');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions')->assertForbidden();
    }
    public function test_dates_budget_and_responsible_must_be_valid_for_the_store(): void
    {
        $other = User::create(['name' => 'Other']); $other->stores()->attach(2);
        $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Sesión', 'status' => 'planned', 'responsible_id' => $other->id, 'budget' => -1, 'shoot_date' => '2026-12-05', 'delivery_date' => '2026-12-01'])
            ->assertUnprocessable()->assertJsonValidationErrors(['responsible_id', 'budget', 'delivery_date']);
        $this->postJson('/api/v1/sorelle/dashboard/photo-sessions', ['name' => 'Sesión', 'status' => 'planned', 'shoot_date' => null, 'delivery_date' => '2026-12-01'])->assertCreated();
    }
    public function test_originals_use_scoped_multipart_plans_without_receiving_the_file_in_php(): void
    {
        $session = $this->photoSessionRecord(); $storage = $this->storage();
        $storage->shouldReceive('configured')->andReturnTrue(); $storage->shouldReceive('begin')->once()->andReturn('b2-upload');
        $response = $this->postJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets', ['name' => 'camera.CR3', 'bytes' => 180000000, 'category' => 'original'])->assertCreated();
        $id = $response->json('data.id'); $asset = PhotoSessionAsset::findOrFail($id);
        $this->assertSame(180000000, $asset->bytes); $this->assertSame('photo', $asset->kind); $this->assertSame('private-photos', $asset->bucket);
        $this->assertSame('photo-thumbs', $asset->preview_bucket);
        $response->assertJsonPath('data.parts', 3)->assertJsonMissingPath('data.upload_id');
        $storage->shouldReceive('partUrl')->once()->withArgs(fn ($record, $part) => $record->id === $id && $part === 2)->andReturn('https://example.test/signed-part');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets/'.$id.'/parts/2')->assertOk();
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets/'.$id.'/parts/4')->assertUnprocessable();
        $storage->shouldReceive('finish')->once()->andReturn(['etag' => 'original-etag']);
        $url = '/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets/'.$id.'/complete';
        $this->postJson($url)->assertOk()->assertJsonPath('data.status', 'ready')->assertJsonMissingPath('data.object_key');
        $this->postJson($url)->assertOk(); // Safe retry after a lost completion response.
        $this->assertSame('ready', $asset->fresh()->status);
        $this->postJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets', ['name' => 'script.html', 'bytes' => 50, 'category' => 'original'])->assertUnprocessable();
    }
    public function test_gallery_and_download_are_scoped_and_originals_never_load_as_thumbnails(): void
    {
        $session = $this->photoSessionRecord(); $asset = $this->asset($session, ['status' => 'ready']);
        $this->asset($session); $foreign = $this->asset($this->photoSessionRecord(2), ['status' => 'ready']);
        $storage = $this->storage();
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.image', null)->assertJsonMissingPath('data.0.bucket');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets/'.$foreign->id.'/download')->assertNotFound();
        $storage->shouldReceive('readUrl')->once()->withArgs(fn ($record) => $record->id === $asset->id)->andReturn('https://example.test/private-original');
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets/'.$asset->id.'/download')->assertOk();
        $this->putJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets/'.$asset->id, ['selected' => true, 'category' => 'final'])->assertOk()->assertJsonPath('data.selected', true);
        $this->getJson('/api/v1/sorelle/dashboard/photo-sessions/'.$session->id.'/assets?selected=1&category=final')->assertOk()->assertJsonPath('total', 1);
    }
    public function test_storage_validates_each_part_and_total_before_completion(): void
    {
        $asset = $this->asset($this->photoSessionRecord(), ['bytes' => 100]);
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('doesObjectExistV2')->andReturnFalse();
        $client->shouldReceive('listParts')->once()->andReturn(new Result(['Parts' => [['PartNumber' => 1, 'Size' => 99, 'ETag' => 'etag']]]));
        $client->shouldNotReceive('completeMultipartUpload');
        $storage = Mockery::mock(PhotoSessionStorage::class)->makePartial(); $storage->shouldReceive('client')->andReturn($client);
        $this->expectException(\Illuminate\Validation\ValidationException::class); $storage->finish($asset);
    }
    public function test_upload_does_not_require_a_private_bucket(): void
    {
        $asset = $this->asset($this->photoSessionRecord(), ['bucket' => 'sorelle-photos']);
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('createMultipartUpload')->once()->withArgs(fn ($args) => $args['Bucket'] === 'sorelle-photos' && $args['Metadata']['asset-id'] === $asset->id)->andReturn(new Result(['UploadId' => 'public-bucket-upload']));
        $storage = Mockery::mock(PhotoSessionStorage::class)->makePartial(); $storage->shouldReceive('client')->andReturn($client);
        $this->assertSame('public-bucket-upload', $storage->begin($asset));
    }
    public function test_signed_originals_and_previews_use_their_saved_buckets_even_after_configuration_changes(): void
    {
        config(['photo-sessions.key' => 'test-key', 'photo-sessions.secret' => 'test-secret', 'photo-sessions.endpoint' => 'https://s3.us-east-005.backblazeb2.com', 'photo-sessions.region' => 'us-east-005']);
        $asset = $this->asset($this->photoSessionRecord(), ['bucket' => 'sorelle-photos', 'preview_bucket' => 'sorelle-thumbs', 'previews' => ['thumb' => 'preview.thumb.webp', 'large' => 'preview.large.webp']]);
        config(['photo-sessions.bucket' => 'another-original-bucket', 'photo-sessions.preview_bucket' => 'another-preview-bucket']);
        $storage = new PhotoSessionStorage();
        $original = $storage->readUrl($asset);
        $thumb = $storage->readUrl($asset, 'thumb');
        $previewUpload = $storage->previewUrl($asset, 'medium', 2000);
        $this->assertStringStartsWith('/sorelle-photos/', parse_url($original, PHP_URL_PATH));
        $this->assertSame('/sorelle-thumbs/preview.thumb.webp', parse_url($thumb, PHP_URL_PATH));
        $this->assertStringStartsWith('/sorelle-thumbs/', parse_url($previewUpload, PHP_URL_PATH));
        parse_str(parse_url($original, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('X-Amz-Signature', $query);
        $this->assertStringContainsString('attachment', $query['response-content-disposition']);
        // Older records had previews in the original bucket; they remain readable.
        $asset->preview_bucket = null;
        $this->assertSame('/sorelle-photos/preview.thumb.webp', parse_url($storage->readUrl($asset, 'thumb'), PHP_URL_PATH));
    }
    public function test_preview_and_original_completion_preserve_bytes_and_use_separate_keys(): void
    {
        $asset = $this->asset($this->photoSessionRecord()); $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('doesObjectExistV2')->andReturnFalse();
        $client->shouldReceive('listParts')->once()->andReturn(new Result(['Parts' => [['PartNumber' => 1, 'Size' => 100, 'ETag' => 'part-etag']]]));
        $client->shouldReceive('completeMultipartUpload')->once()->andReturn(new Result());
        $client->shouldReceive('headObject')->once()->andReturn(new Result(['ContentLength' => 100, 'Metadata' => ['asset-id' => $asset->id], 'ETag' => 'final-etag']));
        $storage = Mockery::mock(PhotoSessionStorage::class)->makePartial(); $storage->shouldReceive('client')->andReturn($client);
        $this->assertSame(['etag' => 'final-etag'], $storage->finish($asset));
        $this->assertSame(100, $asset->fresh()->bytes);
    }
}
