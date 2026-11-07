<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\AreaDashboardController;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Dashboard\AreaDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AreaDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'area_dashboard_test',
            'database.connections.area_dashboard_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
        ]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-06 12:00:00', 'America/Lima'));
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('store_user', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('user_id');
        });
        (require database_path('migrations/2025_11_03_071340_create_permission_tables.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['master', 'ceo', 'sales', 'packing', 'marketing'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'api']);
        }
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->decimal('total', 14, 2);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('name');
            $table->integer('status');
            $table->timestamps();
        });
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable');
        });
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->morphs('priceable');
        });
        DB::table('stores')->insert([
            ['id' => 1, 'name' => 'Sorelle', 'slug' => 'sorelle'],
            ['id' => 2, 'name' => 'Otra tienda', 'slug' => 'otra'],
        ]);
        Route::get('/area-test/{area}', fn (Request $request, string $area) => app(AreaDashboardController::class)->show(Store::findOrFail(1), $request, $area, app(AreaDashboardService::class)));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        DB::purge('area_dashboard_test');
        parent::tearDown();
    }

    private function login(string $role, int $storeId = 1): User
    {
        $user = User::create(['name' => $role]);
        $user->assignRole($role);
        $user->stores()->attach($storeId);
        $this->actingAs($user, 'api');

        return $user;
    }

    public function test_sales_calculates_only_current_store_and_period_and_excludes_cancellations(): void
    {
        $this->login('sales');
        foreach ([[1, 100, 'registrado', '2026-11-05'], [1, 200, 'pago_confirmado', '2026-11-06'], [1, 500, 'cancelado', '2026-11-05'], [2, 9999, 'registrado', '2026-11-06'], [1, 1000, 'preparacion', '2026-09-01']] as [$store, $total, $status, $date]) {
            DB::table('orders')->insert(['store_id' => $store, 'total' => $total, 'status' => $status, 'created_at' => $date.' 10:00:00']);
        }
        $this->getJson('/area-test/sales?days=7')->assertOk()
            ->assertJsonPath('data.sections.0.metrics.0.value', 3)
            ->assertJsonPath('data.sections.0.metrics.1.value', 300)
            ->assertJsonPath('data.sections.0.metrics.2.value', 150)
            ->assertJsonPath('data.sections.0.metrics.3.value', 1)
            ->assertJsonCount(7, 'data.sections.0.trend')
            ->assertJsonCount(3, 'data.sections.0.items');
        $this->getJson('/area-test/ceo')->assertForbidden();
        $this->getJson('/area-test/marketing')->assertForbidden();
        $this->getJson('/area-test/sales?days=999')->assertUnprocessable();
    }

    public function test_packing_includes_backlog_before_the_selected_period_and_omits_amounts(): void
    {
        $this->login('packing');
        DB::table('orders')->insert(['store_id' => 1, 'total' => 400, 'status' => 'preparacion', 'created_at' => '2026-09-01 10:00:00']);
        $this->getJson('/area-test/packing?days=7')->assertOk()
            ->assertJsonPath('data.sections.0.metrics.1.value', 1)
            ->assertJsonCount(1, 'data.sections.0.items')
            ->assertJsonPath('data.sections.0.items.0.value', null);
        $this->getJson('/area-test/sales')->assertForbidden();
    }

    public function test_ceo_consolidates_areas_and_marketing_counts_only_the_current_catalog(): void
    {
        $this->login('ceo');
        foreach ([[1, 'Completo'], [1, 'Pendiente'], [2, 'Ajeno']] as [$store, $name]) {
            DB::table('products')->insert(['store_id' => $store, 'name' => $name, 'status' => 1, 'created_at' => '2026-11-06 10:00:00']);
        }
        DB::table('images')->insert(['imageable_type' => Product::class, 'imageable_id' => 1]);
        DB::table('prices')->insert(['priceable_type' => Product::class, 'priceable_id' => 1]);
        $this->getJson('/area-test/ceo')->assertOk()->assertJsonCount(3, 'data.sections')
            ->assertJsonPath('data.sections.2.metrics.0.value', 2)
            ->assertJsonPath('data.sections.2.metrics.1.value', 1)
            ->assertJsonPath('data.sections.2.items.0.title', 'Pendiente');
        $this->login('marketing');
        $this->getJson('/area-test/marketing')->assertOk()->assertJsonCount(1, 'data.sections');
        $this->getJson('/area-test/packing')->assertForbidden();
        $this->login('ceo', 2);
        $this->getJson('/area-test/ceo')->assertForbidden();
    }

    public function test_legacy_orders_are_reported_as_unavailable_instead_of_zero(): void
    {
        $this->login('ceo');
        Schema::drop('orders');
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        $this->getJson('/area-test/ceo')->assertOk()
            ->assertJsonPath('data.sections.0.available', false)
            ->assertJsonPath('data.sections.0.metrics.0.value', null)
            ->assertJsonPath('data.sections.1.available', false)
            ->assertJsonPath('data.sections.2.available', true);
    }

    private function productionTables(): void
    {
        Schema::create('manufactures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('type');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('manufacture_variant', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manufacture_id');
            $table->unsignedBigInteger('variant_id');
            $table->integer('quantity');
        });
        Schema::create('kardexes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('kardexable_type');
            $table->unsignedBigInteger('kardexable_id');
            $table->unsignedBigInteger('variant_id');
            $table->string('direction');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function test_ceo_tracks_variant_progress_without_cross_store_or_unplanned_receipts(): void
    {
        $this->login('ceo');
        $this->productionTables();
        foreach ([[1, 1, 'production', '2026-01-01'], [2, 1, 'production', '2026-11-05'], [3, 1, 'production', '2026-11-05'], [4, 1, 'order', '2026-11-05'], [5, 2, 'production', '2026-11-05'], [6, 1, 'production', '2026-11-07']] as [$id,$store,$type,$date]) {
            DB::table('manufactures')->insert(['id' => $id, 'store_id' => $store, 'type' => $type, 'name' => 'Production '.$id, 'created_at' => $date.' 10:00:00']);
        }
        foreach ([[1, 10, 10], [1, 11, 10], [2, 12, 5], [4, 13, 1000], [5, 14, 1000], [6, 15, 1000]] as [$manufacture,$variant,$quantity]) {
            DB::table('manufacture_variant')->insert(['manufacture_id' => $manufacture, 'variant_id' => $variant, 'quantity' => $quantity]);
        }
        foreach ([[1, 1, 10, 'in', 20], [1, 1, 10, 'out', 5], [1, 1, 11, 'in', 5], [1, 1, 999, 'in', 50], [1, 2, 12, 'in', 5], [2, 1, 11, 'in', 1000]] as [$store,$manufacture,$variant,$direction,$quantity]) {
            DB::table('kardexes')->insert(['store_id' => $store, 'kardexable_id' => $manufacture, 'kardexable_type' => \App\Models\Manufacture::class, 'variant_id' => $variant, 'direction' => $direction, 'quantity' => $quantity, 'created_at' => '2026-11-05 10:00:00']);
        }
        DB::table('kardexes')->insert(['store_id' => 1, 'kardexable_id' => 1, 'kardexable_type' => \App\Models\Manufacture::class, 'variant_id' => 11, 'direction' => 'in', 'quantity' => 1000, 'created_at' => '2026-11-07 10:00:00']);
        $response = $this->getJson('/area-test/ceo?days=7')->assertOk()
            ->assertJsonPath('data.production_progress.total_count', 3)
            ->assertJsonPath('data.production_progress.active_count', 1)
            ->assertJsonPath('data.production_progress.completed_count', 1)
            ->assertJsonPath('data.production_progress.unplanned_count', 1)
            ->assertJsonPath('data.production_progress.pending_units', 5)
            ->assertJsonPath('data.production_progress.progress_percent', 80);
        $rows = collect($response->json('data.production_progress.items'))->keyBy('id');
        $this->assertSame(75, $rows[1]['progress_percent']);
        $this->assertSame(5, $rows[1]['pending_units']);
        $this->assertSame(55, $rows[1]['extra_units']);
        $this->assertSame('in_progress', $rows[1]['status']);
        $this->assertNull($rows[3]['progress_percent']);
        $this->assertSame(100, $rows[2]['progress_percent']);
        $this->assertDatabaseCount('kardexes', 7);
        $this->login('sales');
        $this->getJson('/area-test/sales')->assertOk()->assertJsonMissingPath('data.production_progress');
        $this->getJson('/area-test/ceo')->assertForbidden();
    }

    public function test_production_progress_does_not_round_incomplete_work_to_one_hundred_percent(): void
    {
        $this->login('ceo');
        $this->productionTables();
        DB::table('manufactures')->insert(['id' => 1, 'store_id' => 1, 'type' => 'production', 'name' => 'Nearly complete', 'created_at' => '2026-11-01 10:00:00']);
        DB::table('manufacture_variant')->insert(['manufacture_id' => 1, 'variant_id' => 10, 'quantity' => 100000]);
        DB::table('kardexes')->insert(['store_id' => 1, 'kardexable_id' => 1, 'kardexable_type' => \App\Models\Manufacture::class, 'variant_id' => 10, 'direction' => 'in', 'quantity' => 99999, 'created_at' => '2026-11-01 10:00:00']);
        $this->getJson('/area-test/ceo')->assertOk()
            ->assertJsonPath('data.production_progress.items.0.progress_percent', 99.9)
            ->assertJsonPath('data.production_progress.items.0.status', 'in_progress')
            ->assertJsonPath('data.production_progress.items.0.pending_units', 1);
    }

    public function test_empty_and_missing_production_structures_are_distinguished(): void
    {
        $this->login('ceo');
        $this->getJson('/area-test/ceo')->assertOk()->assertJsonPath('data.production_progress.available', false);
        $this->productionTables();
        $this->getJson('/area-test/ceo')->assertOk()->assertJsonPath('data.production_progress.available', true)
            ->assertJsonPath('data.production_progress.total_count', 0)
            ->assertJsonPath('data.production_progress.progress_percent', null);
    }

    public function test_old_production_stays_visible_when_its_reception_completes_in_the_period(): void
    {
        $this->login('ceo');
        $this->productionTables();
        DB::table('manufactures')->insert(['id' => 1, 'store_id' => 1, 'type' => 'production', 'name' => 'Old production', 'created_at' => '2026-01-01 10:00:00']);
        DB::table('manufacture_variant')->insert(['manufacture_id' => 1, 'variant_id' => 10, 'quantity' => 5]);
        DB::table('kardexes')->insert(['store_id' => 1, 'kardexable_id' => 1, 'kardexable_type' => \App\Models\Manufacture::class, 'variant_id' => 10, 'direction' => 'in', 'quantity' => 5, 'created_at' => '2026-11-05 10:00:00']);
        $this->getJson('/area-test/ceo?days=7')->assertOk()
            ->assertJsonPath('data.production_progress.items.0.status', 'completed')
            ->assertJsonPath('data.production_progress.items.0.progress_percent', 100);
    }
}
