<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Store;
use App\Models\User;
use App\Services\Dashboard\Crud\AttendanceService;
use App\Services\Dashboard\Employee\EmployeeLaborSummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndependentEmployeeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'personnel_test',
            'database.connections.personnel_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
            'cache.default' => 'array',
        ]);
        Http::preventStrayRequests();
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
        });
        Schema::create('identities', fn (Blueprint $table) => $table->id());
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('identity_id')->nullable();
            $table->string('document_number')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('store_user', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('user_id');
        });
        (require database_path('migrations/2025_11_03_071340_create_permission_tables.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['master', 'ceo', 'sales'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'api']);
        }
        (require database_path('migrations/0001_03_01_000140_create_employees_table.php'))->up();
        (require database_path('migrations/2026_03_14_161143_create_attendances_table.php'))->up();
        (require database_path('migrations/2026_05_14_132534_create_employee_schedules_table.php'))->up();
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('direction');
            $table->string('comment')->nullable();
            $table->date('date');
            $table->unsignedBigInteger('gateway_id')->nullable();
            $table->morphs('paymentable');
            $table->timestamps();
        });
        Schema::create('gateways', fn (Blueprint $table) => $table->id());
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable');
        });
        DB::table('stores')->insert([
            ['id' => 1, 'name' => 'Sorelle', 'slug' => 'sorelle'],
            ['id' => 2, 'name' => 'Other', 'slug' => 'other'],
        ]);
        $user = User::unguarded(fn () => User::create([
            'name' => 'Original name',
            'email' => 'old@example.test',
            'phone' => '999999999',
            'document_number' => '12345678',
            'password' => 'Original-password1',
        ]));
        $user->assignRole('ceo');
        $user->stores()->attach(1);
        DB::table('employees')->insert([
            'id' => 3, 'user_id' => $user->id, 'salary' => 1500, 'type' => 'fulltime',
            'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00',
        ]);
        DB::table('attendances')->insert([
            'store_id' => 1, 'employee_id' => 3, 'date' => '2026-11-02', 'check_in' => '09:00:00', 'check_out' => '19:00:00',
        ]);
        (require database_path('migrations/2026_11_06_170000_make_employees_independent_from_users.php'))->up();
        (require database_path('migrations/2026_11_07_010000_require_employee_user_accounts.php'))->up();
        (require database_path('migrations/2026_11_07_020000_remove_duplicate_employee_identity_fields.php'))->up();
        (require database_path('migrations/2026_11_07_030000_keep_only_work_fields_on_employees.php'))->up();
        (require database_path('migrations/2026_11_07_040000_restore_employee_work_status.php'))->up();
        $this->actingAs($user->fresh(), 'api');
    }

    protected function tearDown(): void
    {
        DB::purge('personnel_test');
        parent::tearDown();
    }

    public function test_backfill_preserves_identity_links_and_account_deletion_is_restricted(): void
    {
        $employee = Employee::findOrFail(3);
        $this->assertSame('Original name', $employee->name);
        $this->assertSame('12345678', $employee->document_number);
        $this->assertSame(['ceo'], $employee->roles);
        $this->assertSame('1', $employee->biometric_code);
        $this->assertSame(1, $employee->attendances()->count());
        $this->assertFalse(Schema::hasColumn('employees', 'name'));
        $this->assertFalse(Schema::hasColumn('employees', 'email'));
        $this->assertTrue(Schema::hasColumn('employees', 'status'));
        foreach (['phone', 'identity_id', 'document_number', 'roles'] as $field) {
            $this->assertFalse(Schema::hasColumn('employees', $field));
        }
        try {
            DB::table('users')->where('id', $employee->user_id)->delete();
            $this->fail('An employee account cannot be deleted while associated.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertNotNull($employee->fresh()->user_id);
        }
        $this->assertSame('Original name', $employee->fresh()->name);
        $this->assertDatabaseHas('attendances', ['employee_id' => 3]);
    }

    public function test_employee_account_is_created_with_roles_and_existing_password_is_preserved_on_edit(): void
    {
        $response = $this->postJson('/api/v1/sorelle/dashboard/employees', [
            'name' => 'Independent person', 'roles' => ['sales'], 'email' => 'staff@example.test',
            'password' => 'Employee-password1', 'password_confirmation' => 'Employee-password1',
        ])->assertCreated()->assertJsonPath('data.name', 'Independent person')->assertJsonPath('data.account.email', 'staff@example.test');
        $id = $response->json('data.id');
        $this->assertDatabaseCount('users', 2);
        $createdUser = User::findOrFail($response->json('data.user_id'));
        $this->assertTrue(Hash::check('Employee-password1', $createdUser->password));
        $this->assertTrue($createdUser->hasRole('sales'));
        $this->assertTrue($createdUser->stores()->whereKey(1)->exists());
        $this->assertTrue(Auth::guard('web')->validate(['email' => 'staff@example.test', 'password' => 'Employee-password1', 'status' => 1]));
        $this->assertStringNotContainsString('Employee-password1', $response->getContent());
        $passwordHash = $createdUser->password;
        $this->putJson('/api/v1/sorelle/dashboard/employees/'.$id, ['name' => 'Changed person', 'biometric_code' => '0'])->assertOk();
        $this->assertDatabaseHas('employees', ['id' => $id, 'biometric_code' => '0']);
        $this->assertSame($passwordHash, $createdUser->fresh()->password);
        $this->putJson('/api/v1/sorelle/dashboard/employees/3', ['name' => 'New employee name', 'email' => 'new@example.test', 'roles' => ['sales']])->assertOk();
        $this->assertDatabaseHas('users', ['id' => 1, 'name' => 'New employee name', 'email' => 'new@example.test']);
        $this->assertTrue(User::find(1)->hasRole('sales'));
        $this->getJson('/api/v1/sorelle/dashboard/employees')->assertOk()->assertJsonMissingPath('data.0.user');
        $this->postJson('/api/v1/sorelle/dashboard/employees/search', ['search' => 'New employee'])->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_attendance_without_weekly_schedule_is_visible_and_uses_employee_name(): void
    {
        Employee::find(3)->update(['date_entry' => '2026-11-03']);
        $this->getJson('/api/v1/sorelle/dashboard/employees/3')->assertOk()
            ->assertJsonPath('data.attendances.0.employee_name', 'Original name');
        $this->getJson('/api/v1/sorelle/dashboard/attendances')->assertOk()
            ->assertJsonPath('data.0.employee_name', 'Original name')->assertJsonPath('meta.total', 1);
        $this->postJson('/api/v1/sorelle/dashboard/attendances', [
            'employee_id' => 3, 'date' => '2026-11-03', 'check_in' => '09:00', 'check_out' => '18:00',
        ])->assertOk()->assertJsonPath('data.minutes', 540);
        $this->postJson('/api/v1/sorelle/dashboard/attendances', [
            'employee_id' => 3, 'date' => '2026-11-03', 'check_in' => '09:00',
        ])->assertUnprocessable();
        $this->postJson('/api/v1/sorelle/dashboard/attendances', [
            'employee_id' => 3, 'date' => '2026-11-04', 'check_in' => '20:00', 'check_out' => '18:00',
        ])->assertUnprocessable();
    }

    public function test_cross_store_employee_attendance_schedules_and_search_are_rejected(): void
    {
        $outsider = $this->outsideEmployee();
        $this->getJson('/api/v1/sorelle/dashboard/employees/'.$outsider->id)->assertNotFound();
        $this->postJson('/api/v1/sorelle/dashboard/attendances', [
            'employee_id' => $outsider->id, 'date' => '2026-11-03', 'check_in' => '09:00',
        ])->assertUnprocessable();
        $this->getJson('/api/v1/other/dashboard/employees')->assertForbidden();
        $this->getJson('/api/v1/sorelle/dashboard/employees/'.$outsider->id.'/schedules')->assertNotFound();
        $this->postJson('/api/v1/sorelle/dashboard/employees/search', ['search' => 'Outside'])->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_csv_uses_employee_document_or_clock_code_and_refuses_unknown_codes_atomically(): void
    {
        $csv = "Header\nHeader2\n0;1;Person;03/11/2026 09:00\n0;1;Person;03/11/2026 18:00\n0;12345678;Person;04/11/2026 09:15\n";
        $request = Request::create('/test', 'POST');
        $request->files->set('file', UploadedFile::fake()->createWithContent('clock.csv', $csv));
        $service = app(AttendanceService::class);
        $result = $service->upload(Store::find(1), $request);
        $this->assertSame(2, $result['processed_days']);
        $this->assertDatabaseHas('attendances', ['employee_id' => 3, 'date' => '2026-11-04', 'check_out' => null]);
        $service->upload(Store::find(1), $request);
        $this->assertDatabaseCount('attendances', 3);
        $request = Request::create('/test', 'POST');
        $request->files->set('file', UploadedFile::fake()->createWithContent('unknown.csv', $csv."0;UNKNOWN;Person;05/11/2026 09:00\n"));
        try {
            $service->upload(Store::find(1), $request);
            $this->fail('Unknown clock codes must stop the entire upload.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
        $this->assertDatabaseCount('attendances', 3);
    }

    public function test_weekly_schedules_work_without_login_and_preserve_attendance_after_removal(): void
    {
        $values = ['work_type' => 'home_office', 'day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '18:00', 'allow_extra_hours' => false];
        $response = $this->postJson('/api/v1/sorelle/dashboard/employees/3/schedules', $values)->assertOk();
        $id = $response->json('data.id');
        $this->postJson('/api/v1/sorelle/dashboard/employees/3/schedules', $values)->assertUnprocessable();
        $this->postJson('/api/v1/sorelle/dashboard/employees/3/attendances/search', [
            'start_date' => '2026-11-02', 'end_date' => '2026-11-03',
        ])->assertOk()->assertJsonPath('data.0.work_type', 'home_office');
        $this->deleteJson('/api/v1/sorelle/dashboard/employees/3/schedules/'.$id)->assertOk();
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_employee_balance_uses_its_own_payments_instead_of_payments_recorded_by_its_user(): void
    {
        DB::table('payments')->insert([
            ['store_id' => 1, 'user_id' => 999, 'amount' => 70, 'direction' => 'out', 'date' => '2026-11-01', 'paymentable_type' => Employee::class, 'paymentable_id' => 3],
            ['store_id' => 1, 'user_id' => 1, 'amount' => 999, 'direction' => 'out', 'date' => '2026-11-01', 'paymentable_type' => Employee::class, 'paymentable_id' => 999],
        ]);
        $this->getJson('/api/v1/sorelle/dashboard/employees/3')->assertOk()->assertJsonPath('data.balance', -70);
        $this->getJson('/api/v1/sorelle/dashboard/employees/3/payments')->assertOk()->assertJsonCount(1, 'data.employee_payments');
    }

    public function test_labor_summary_shows_current_and_previous_two_fortnights_without_future_absences(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-06 12:00', 'America/Lima'));
        try {
            Employee::find(3)->schedules()->create([
                'store_id' => 1, 'day_of_week' => 5, 'work_type' => 'onsite',
                'start_time' => '09:00:00', 'end_time' => '19:00:00', 'allow_extra_hours' => false,
            ]);
            DB::table('attendances')->insert([
                'store_id' => 1, 'employee_id' => 3, 'date' => '2026-11-08',
                'check_in' => '09:00:00', 'check_out' => '19:00:00',
            ]);
            $response = $this->getJson('/api/v1/sorelle/dashboard/employees/3/labor-summary')->assertOk()
                ->assertJsonCount(3, 'data.periods')
                ->assertJsonPath('data.periods.0.start_date', '2026-11-01')
                ->assertJsonPath('data.periods.0.end_date', '2026-11-15')
                ->assertJsonPath('data.periods.0.computed_through', '2026-11-06')
                ->assertJsonPath('data.periods.0.recorded_days', 1)
                ->assertJsonPath('data.periods.0.missing_days', 1)
                ->assertJsonPath('data.periods.1.start_date', '2026-10-16')
                ->assertJsonPath('data.periods.1.end_date', '2026-10-31')
                ->assertJsonPath('data.periods.2.start_date', '2026-10-01');
            foreach ($response->json('data.periods.0.attendances') as $row) {
                $this->assertLessThanOrEqual('2026-11-06', $row['date']);
            }
            $outsider = $this->outsideEmployee();
            $this->getJson('/api/v1/sorelle/dashboard/employees/'.$outsider->id.'/labor-summary')->assertNotFound();
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_fortnight_boundaries_cover_february_leap_years_and_year_changes(): void
    {
        try {
            foreach ([
                ['2026-03-01', '2026-02-28', '2026-02-01'],
                ['2028-03-01', '2028-02-29', '2028-02-01'],
                ['2026-01-15', '2025-12-31', '2025-12-01'],
                ['2026-01-16', '2026-01-15', '2025-12-16'],
            ] as [$today, $previousEnd, $oldestStart]) {
                CarbonImmutable::setTestNow(CarbonImmutable::parse($today.' 12:00', 'America/Lima'));
                $summary = app(EmployeeLaborSummaryService::class)->forEmployee(Store::find(1), 3);
                $this->assertSame($previousEnd, $summary['periods'][1]['end_date']);
                $this->assertSame($oldestStart, $summary['periods'][2]['start_date']);
                $this->assertCount(3, $summary['periods']);
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function outsideEmployee(): Employee
    {
        $user = User::unguarded(fn () => User::create(['name' => 'Outside', 'email' => 'outside@example.test', 'password' => 'Outside-password1']));

        return Employee::create(['store_id' => 2, 'user_id' => $user->id]);
    }

    public function test_account_credentials_are_required_and_existing_emails_cannot_be_taken_over(): void
    {
        $this->postJson('/api/v1/sorelle/dashboard/employees', ['name' => 'No login'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password', 'roles']);
        $this->postJson('/api/v1/sorelle/dashboard/employees', [
            'name' => 'Duplicate login', 'email' => 'old@example.test', 'roles' => ['sales'],
            'password' => 'Employee-password1', 'password_confirmation' => 'Employee-password1',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_account_and_employee_creation_roll_back_together(): void
    {
        Employee::creating(function ($employee) {
            if ($employee->name === 'Rollback') {
                throw new \RuntimeException('Simulated employee failure');
            }
        });
        try {
            app(\App\Services\Dashboard\Employee\EmployeeAccountService::class)->save(Store::find(1), [
                'name' => 'Rollback', 'email' => 'rollback@example.test',
                'password' => 'Employee-password1', 'roles' => ['sales'],
            ]);
            $this->fail('A failed employee insert must roll back its account.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated employee failure', $exception->getMessage());
        } finally {
            Employee::flushEventListeners();
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('store_user', 1);
        $this->assertDatabaseCount('model_has_roles', 1);
    }

    public function test_password_changes_are_explicit_and_archiving_blocks_existing_erp_access(): void
    {
        $this->putJson('/api/v1/sorelle/dashboard/employees/3', [
            'name' => 'Original name', 'password' => 'Changed-password1', 'password_confirmation' => 'Mismatch-password1',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertTrue(Hash::check('Original-password1', User::find(1)->password));
        $this->putJson('/api/v1/sorelle/dashboard/employees/3', [
            'name' => 'Original name', 'password' => 'Changed-password1', 'password_confirmation' => 'Changed-password1',
        ])->assertOk();
        $this->assertTrue(Hash::check('Changed-password1', User::find(1)->password));
        $this->deleteJson('/api/v1/sorelle/dashboard/employees/3')->assertOk();
        $this->assertDatabaseHas('users', ['id' => 1, 'status' => 0]);
        $this->assertFalse(Employee::find(3)->status);
        $this->assertDatabaseCount('attendances', 1);
        $this->actingAs(User::find(1), 'api');
        $this->getJson('/api/v1/sorelle/dashboard/employees')->assertForbidden();
        $this->getJson('/api/v1/sorelle/dashboard/shopify/orders')->assertForbidden();
    }

    public function test_identity_changes_in_users_are_reflected_in_employee_search_attendance_and_summary(): void
    {
        DB::table('users')->where('id', 1)->update([
            'name' => 'Canonical account name', 'email' => 'canonical@example.test',
            'phone' => '888888888', 'document_number' => '87654321',
        ]);
        $this->getJson('/api/v1/sorelle/dashboard/employees')->assertOk()
            ->assertJsonPath('data.0.name', 'Canonical account name')
            ->assertJsonPath('data.0.email', 'canonical@example.test')
            ->assertJsonPath('data.0.phone', '888888888')
            ->assertJsonPath('data.0.document_number', '87654321')
            ->assertJsonMissingPath('data.0.user');
        $this->postJson('/api/v1/sorelle/dashboard/employees/search', ['search' => 'canonical@example.test'])
            ->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/sorelle/dashboard/employees/search', ['search' => 'Original name'])
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/sorelle/dashboard/attendances')->assertOk()
            ->assertJsonPath('data.0.employee_name', 'Canonical account name');
        $this->getJson('/api/v1/sorelle/dashboard/employees/3/labor-summary')->assertOk()
            ->assertJsonPath('data.employee.name', 'Canonical account name');
        $attributes = Employee::find(3)->getAttributes();
        $this->assertArrayNotHasKey('name', $attributes);
        $this->assertArrayNotHasKey('email', $attributes);
        foreach (['phone', 'identity_id', 'document_number', 'roles'] as $field) {
            $this->assertArrayNotHasKey($field, $attributes);
        }
    }

    public function test_work_status_is_stored_on_employee_and_can_differ_from_account_access(): void
    {
        $this->assertTrue(Employee::find(3)->status);
        DB::table('users')->where('id', 1)->update(['status' => false]);
        $this->assertTrue(Employee::find(3)->status);
        $this->assertFalse((bool) Employee::find(3)->user->status);
        DB::table('users')->where('id', 1)->update(['status' => true]);

        $this->putJson('/api/v1/sorelle/dashboard/employees/3', [
            'name' => 'Original name', 'status' => false,
        ])->assertOk()->assertJsonPath('data.status', false);
        $this->assertDatabaseHas('employees', ['id' => 3, 'status' => 0]);
        $this->assertDatabaseHas('users', ['id' => 1, 'status' => 0]);
    }

    public function test_personal_fields_are_updated_in_users_and_remain_searchable_for_attendance(): void
    {
        $this->putJson('/api/v1/sorelle/dashboard/employees/3', [
            'name' => 'Original name', 'phone' => '777777777', 'document_number' => '87654321',
        ])->assertOk()->assertJsonPath('data.phone', '777777777');
        $this->assertDatabaseHas('users', ['id' => 1, 'phone' => '777777777', 'document_number' => '87654321']);
        $this->postJson('/api/v1/sorelle/dashboard/employees/search', ['search' => '87654321'])
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/sorelle/dashboard/attendances?search=777777777')
            ->assertOk()->assertJsonCount(1, 'data');
    }
}
