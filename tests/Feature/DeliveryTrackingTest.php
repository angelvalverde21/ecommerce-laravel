<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Dashboard\DeliveryTrackingDashboardController;
use App\Models\DeliveryTracking;
use App\Models\Manufacture;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\User;
use App\Services\Delivery\DeliveryOverviewService;
use App\Services\Delivery\DeliveryTrackingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryTrackingTest extends TestCase
{
    private Purchase $purchase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'delivery_test', 'database.connections.delivery_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        $this->travelTo(Carbon::parse('2026-11-30 12:00:00', 'America/Lima'));
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('store_user', function (Blueprint $table) {
            $table->integer('store_id');
            $table->integer('user_id');
        });
        Schema::create('manufactures', function (Blueprint $table) {
            $table->id();
            $table->integer('store_id');
            $table->string('type');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_address_id')->nullable();
            $table->id();
            $table->integer('store_id');
            $table->morphs('purchaseable');
            $table->integer('production_stage_id')->nullable();
            $table->timestamps();
        });
        Schema::create('production_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->integer('purchase_id');
            $table->string('name');
        });
        (require database_path('migrations/2025_11_03_071340_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_11_13_100000_create_delivery_tracking_tables.php'))->up();
        DB::table('stores')->insert([['id' => 1, 'slug' => 'one'], ['id' => 2, 'slug' => 'two']]);
        // Insert directly: this fixture only needs a parent record, not production-stage creation.
        DB::table('manufactures')->insert(['id' => 1, 'store_id' => 1, 'type' => 'production', 'name' => 'Blusa Renata']);
        $this->purchase = Manufacture::findOrFail(1)->purchases()->create(['store_id' => 1]);
        $user = User::create(['name' => 'Ana']);
        DB::table('store_user')->insert(['store_id' => 1, 'user_id' => $user->id]);
        $this->actingAs($user, 'api');
        Route::post('/delivery-test/{storeId}', fn (Request $request, $storeId) => app(DeliveryTrackingDashboardController::class)->store(Store::findOrFail($storeId), $request, app(DeliveryTrackingService::class)));
        Route::put('/delivery-test/{storeId}/{id}', fn (Request $request, $storeId, $id) => app(DeliveryTrackingDashboardController::class)->update(Store::findOrFail($storeId), $request, $id, app(DeliveryTrackingService::class)));
        Route::get('/delivery-test/{storeId}/{id}', fn (Request $request, $storeId, $id) => app(DeliveryTrackingDashboardController::class)->show(Store::findOrFail($storeId), $request, $id));
        Route::get('/delivery-test/{storeId}/{id}/events', fn (Request $request, $storeId, $id) => app(DeliveryTrackingDashboardController::class)->events(Store::findOrFail($storeId), $request, $id));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::purge('delivery_test');
        parent::tearDown();
    }

    private function createTracking(array $dates = []): int
    {
        return $this->postJson('/delivery-test/1', $dates + [
            'trackable_type' => 'purchase', 'trackable_id' => $this->purchase->id,
            'handed_over_on' => '2026-11-20', 'expected_on' => '2026-11-25',
        ])->assertOk()->json('data.id');
    }

    public function test_production_count_includes_received_purchases_without_counting_events_or_other_owners(): void
    {
        $count = fn () => (int) Manufacture::withDeliveryTrackingCount()->findOrFail(1)->tracked_purchases_count;
        $this->assertSame(0, $count());
        $id = $this->createTracking();
        $this->putJson('/delivery-test/1/'.$id, ['revision' => 0, 'expected_on' => '2026-12-01', 'reason' => 'Nuevo acuerdo'])->assertOk();
        $this->assertSame(1, $count());
        $production = Manufacture::findOrFail(1);
        $received = $production->purchases()->create(['store_id' => 1]);
        $receivedTracking = $this->postJson('/delivery-test/1', ['trackable_type' => 'purchase', 'trackable_id' => $received->id, 'expected_on' => '2026-12-01'])->assertOk()->json('data.id');
        $this->putJson('/delivery-test/1/'.$receivedTracking, ['revision' => 0, 'received_on' => '2026-11-30'])->assertOk();
        $production->purchases()->create(['store_id' => 1]);
        $other = Manufacture::create(['store_id' => 1, 'type' => 'production', 'name' => 'Otra']);
        $otherPurchase = $other->purchases()->create(['store_id' => 1]);
        $this->postJson('/delivery-test/1', ['trackable_type' => 'purchase', 'trackable_id' => $otherPurchase->id, 'expected_on' => '2026-12-01'])->assertOk();
        $this->assertSame(2, $count());
        $received->delete();
        $this->assertSame(1, $count());
        $foreign = $production->purchases()->create(['store_id' => 2]);
        $foreign->deliveryTracking()->create(['store_id' => 2, 'expected_on' => '2026-12-01']);
        $this->assertSame(1, $count());
    }

    public function test_tracking_is_optional_and_duplicate_or_unsupported_owners_are_rejected(): void
    {
        $this->assertNull($this->purchase->deliveryTracking);
        $id = $this->createTracking();
        $this->getJson('/delivery-test/1/'.$id)->assertOk()->assertJsonPath('data.status', 'overdue');
        $this->postJson('/delivery-test/1', ['trackable_type' => 'purchase', 'trackable_id' => $this->purchase->id, 'expected_on' => '2026-12-01'])->assertUnprocessable();
        $this->postJson('/delivery-test/1', ['trackable_type' => 'App\\Models\\User', 'trackable_id' => 1, 'expected_on' => '2026-12-01'])->assertUnprocessable();
        $this->assertSame(1, DeliveryTracking::count());
    }

    public function test_reprogramming_is_audited_and_stale_edits_do_not_overwrite_dates(): void
    {
        $id = $this->createTracking();
        $url = '/delivery-test/1/'.$id;
        $this->putJson($url, ['revision' => 0, 'expected_on' => '2026-11-28'])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->putJson($url, ['revision' => 0, 'expected_on' => '2026-11-28', 'reason' => 'Retraso del taller'])->assertOk()->assertJsonPath('data.revision', 1)->assertJsonPath('data.reschedules_count', 1);
        $this->putJson($url, ['revision' => 0, 'expected_on' => '2026-12-01', 'reason' => 'Otro acuerdo'])->assertUnprocessable()->assertJsonValidationErrors('revision');
        $this->getJson($url.'/events')->assertOk()->assertJsonPath('data.0.changes.expected_on.before', '2026-11-25')->assertJsonPath('data.0.changes.expected_on.after', '2026-11-28')->assertJsonPath('data.0.actor_name', 'Ana')->assertJsonCount(2, 'data');
    }

    public function test_receipt_and_corrections_keep_committed_dates_and_require_audit_reasons(): void
    {
        $id = $this->createTracking();
        $url = '/delivery-test/1/'.$id;
        $this->putJson($url, ['revision' => 0, 'received_on' => '2026-11-27'])->assertOk()->assertJsonPath('data.status', 'received')->assertJsonPath('data.received_late', true);
        $this->putJson($url, ['revision' => 1, 'received_on' => '2026-11-24'])->assertUnprocessable();
        $this->putJson($url, ['revision' => 1, 'received_on' => '2026-11-24', 'reason' => 'Corrección del comprobante de recojo'])->assertOk()->assertJsonPath('data.received_late', false);
        $this->putJson($url, ['revision' => 2, 'expected_on' => '2026-11-27', 'reason' => 'Cambio'])->assertUnprocessable();
        $this->assertSame(3, DB::table('delivery_tracking_events')->count());
    }

    public function test_invalid_dates_do_not_create_partial_tracking_or_history(): void
    {
        $base = ['trackable_type' => 'purchase', 'trackable_id' => $this->purchase->id];
        $this->postJson('/delivery-test/1', $base + ['expected_on' => 'wrong'])->assertUnprocessable();
        $this->postJson('/delivery-test/1', $base + ['expected_on' => '2026-11-19', 'handed_over_on' => '2026-11-20'])->assertUnprocessable();
        $this->postJson('/delivery-test/1', $base + ['expected_on' => '2026-12-01', 'received_on' => '2026-12-02'])->assertUnprocessable();
        $this->assertSame(0, DeliveryTracking::count());
        $this->assertSame(0, DB::table('delivery_tracking_events')->count());
    }

    public function test_store_boundaries_apply_to_creation_updates_and_history(): void
    {
        $id = $this->createTracking();
        $this->getJson('/delivery-test/2/'.$id)->assertForbidden();
        $other = Manufacture::findOrFail(1)->purchases()->create(['store_id' => 2]);
        $this->postJson('/delivery-test/1', ['trackable_type' => 'purchase', 'trackable_id' => $other->id, 'expected_on' => '2026-12-01'])->assertNotFound();
        DeliveryTracking::whereKey($id)->update(['store_id' => 2]);
        $this->putJson('/delivery-test/1/'.$id, ['revision' => 0, 'received_on' => '2026-11-25'])->assertNotFound();
        $this->getJson('/delivery-test/1/'.$id.'/events')->assertNotFound();
    }

    public function test_full_history_is_paginated_and_ceo_sees_pending_and_recent_changes(): void
    {
        $id = $this->createTracking();
        for ($index = 0; $index < 22; $index++) {
            $this->putJson('/delivery-test/1/'.$id, ['revision' => $index, 'expected_on' => Carbon::parse('2026-12-01')->addDays($index)->toDateString(), 'reason' => 'Acuerdo '.$index])->assertOk();
        }
        $this->getJson('/delivery-test/1/'.$id.'/events')->assertOk()->assertJsonPath('total', 23)->assertJsonCount(20, 'data');
        $this->getJson('/delivery-test/1/'.$id.'/events?page=2')->assertOk()->assertJsonCount(3, 'data');
        $summary = app(DeliveryOverviewService::class)->forStore(Store::findOrFail(1));
        $this->assertSame(1, $summary['pending']);
        $this->assertSame(0, $summary['overdue']);
        $this->assertCount(1, $summary['recent_changes']);
        $this->assertSame('Acuerdo 21', $summary['recent_changes'][0]['last_change']->reason);
        $this->assertSame('Blusa Renata', $summary['items'][0]['production']);
    }
}
