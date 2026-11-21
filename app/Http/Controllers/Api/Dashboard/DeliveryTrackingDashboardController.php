<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DeliveryTracking;
use App\Models\Purchase;
use App\Models\Store;
use App\Services\Delivery\DeliveryTrackingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryTrackingDashboardController extends Controller
{
    private const OWNERS = ['purchase' => Purchase::class];

    private function authorizeStore(Store $store, Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->stores()->where('stores.id', $store->id)->exists() || $user->hasRole('master')), 403);
    }

    private function dates(bool $creating): array
    {
        return [
            'handed_over_on' => 'sometimes|nullable|date_format:Y-m-d',
            'expected_on' => ($creating ? 'required' : 'sometimes').'|date_format:Y-m-d',
            'received_on' => 'sometimes|nullable|date_format:Y-m-d',
            'reason' => 'nullable|string|max:2000',
        ];
    }

    public function store(Store $store, Request $request, DeliveryTrackingService $service)
    {
        $this->authorizeStore($store, $request);
        $data = $request->validate($this->dates(true) + [
            'trackable_type' => ['required', Rule::in(array_keys(self::OWNERS))], 'trackable_id' => 'required|integer',
        ]);
        $owner = self::OWNERS[$data['trackable_type']]::where('store_id', $store->id)->findOrFail($data['trackable_id']);

        return responseOk($service->create($owner, $data, $request->user()));
    }

    public function show(Store $store, Request $request, int $tracking_id)
    {
        $this->authorizeStore($store, $request);

        return responseOk(DeliveryTracking::where('store_id', $store->id)->findOrFail($tracking_id)->loadCount('reschedules')->load('scheduleHistory'));
    }

    public function events(Store $store, Request $request, int $tracking_id)
    {
        $this->authorizeStore($store, $request);

        return DeliveryTracking::where('store_id', $store->id)->findOrFail($tracking_id)->events()->paginate(20);
    }

    public function update(Store $store, Request $request, int $tracking_id, DeliveryTrackingService $service)
    {
        $this->authorizeStore($store, $request);
        $tracking = DeliveryTracking::where('store_id', $store->id)->findOrFail($tracking_id);
        $data = $request->validate($this->dates(false) + ['revision' => 'required|integer|min:0']);

        return responseOk($service->update($tracking, $data, $request->user()));
    }
}
