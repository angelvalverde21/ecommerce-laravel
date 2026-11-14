<?php

namespace App\Services\Delivery;

use App\Models\DeliveryTracking;
use App\Models\Manufacture;
use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Support\Facades\Schema;

class DeliveryOverviewService
{
    public function forStore(Store $store): array
    {
        if (! Schema::hasTable('delivery_trackings')) {
            return ['pending' => 0, 'overdue' => 0, 'today' => 0, 'items' => [], 'recent_changes' => []];
        }
        $today = now('America/Lima')->toDateString();
        $query = DeliveryTracking::where('store_id', $store->id)
            ->whereHasMorph('trackable', [Purchase::class], fn ($query) => $query->where('store_id', $store->id))
            ->with(['trackable.purchaseable', 'trackable.items', 'trackable.productionStage'])->withCount('reschedules');
        $pending = (clone $query)->whereNull('received_on');
        $items = (clone $pending)->orderBy('expected_on')->orderBy('id')->limit(8)->get();
        $changes = (clone $query)->whereHas('reschedules', fn ($events) => $events->where('created_at', '>=', now()->subDays(7)))
            ->with(['reschedules' => fn ($events) => $events->limit(1)])
            ->orderByDesc('updated_at')->limit(8)->get();

        return [
            'pending' => (clone $pending)->count(),
            'overdue' => (clone $pending)->where('expected_on', '<', $today)->count(),
            'today' => (clone $pending)->where('expected_on', $today)->count(),
            'items' => $items->map(fn ($tracking) => $this->item($tracking))->values(),
            'recent_changes' => $changes->map(fn ($tracking) => $this->item($tracking) + ['last_change' => $tracking->reschedules->first()])->values(),
        ];
    }

    private function item(DeliveryTracking $tracking): array
    {
        $purchase = $tracking->trackable;
        $owner = $purchase->purchaseable;
        $production = $owner instanceof Manufacture && $owner->type === 'production';

        return [
            'id' => $tracking->id, 'purchase_id' => $purchase->id,
            'title' => $purchase->items->first()?->name ?? 'Compra #'.$purchase->id,
            'production' => $owner?->name, 'stage' => $purchase->productionStage?->name,
            'stage_id' => $purchase->production_stage_id,
            'stage_slug' => $purchase->productionStage?->slug,
            'expected_on' => $tracking->expected_on->format('Y-m-d'), 'status' => $tracking->status,
            'reschedules_count' => $tracking->reschedules_count,
            'path' => $production ? 'manufactures/productions/'.$owner->id.'/purchases' : null,
        ];
    }
}
