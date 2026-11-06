<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Inventory, InventoryCount, Store};
use App\Services\Inventory\InventoryWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class InventoryCountDashboardController extends Controller
{
    public function index(Store $store, Request $request, string $inventory_id, string $session_id)
    {
        $data = $request->validate(['variant_id' => 'nullable|integer|min:1', 'location_id' => 'nullable|integer|min:1', 'grouped' => 'sometimes|boolean']);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $query = $inventory->sessions()->findOrFail($session_id)->counts()
            ->when(isset($data['variant_id']), fn ($query) => $query->where('variant_id', $data['variant_id']))
            ->when(isset($data['location_id']), fn ($query) => $query->where('location_id', $data['location_id']));
        if ($request->boolean('grouped')) {
            $groups = (clone $query)->select('variant_id')->selectRaw('MAX(id) as latest_id')->groupBy('variant_id')->orderByDesc('latest_id')->paginate(20)->withQueryString();
            $ids = $groups->getCollection()->pluck('variant_id');
            $captures = (clone $query)->whereIn('variant_id', $ids)->with(['location.warehouse', 'variant.product:id,name', 'user:id,name'])->orderByDesc('counted_at')->orderByDesc('id')->get();
            return response()->json($groups->setCollection($captures));
        }
        $result = $query->with(['location.warehouse', 'variant.product:id,name', 'user:id,name'])->orderByDesc('counted_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $result = $result->toArray();
        if (isset($data['variant_id'])) {
            $result['variant_counted_quantity'] = (int) $inventory->counts()->where('variant_id', $data['variant_id'])->where('inventory_counts.status', 'valid')->sum('quantity');
        }
        return response()->json($result);
    }

    public function show(Store $store, string $inventory_id, string $session_id, string $count_id)
    {
        return responseOk($store->inventories()->findOrFail($inventory_id)->sessions()->findOrFail($session_id)->counts()
            ->with(['location.warehouse', 'variant.product:id,name', 'user:id,name'])->findOrFail($count_id));
    }

    public function store(Store $store, Request $request, string $inventory_id, string $session_id, InventoryWorkspace $workspace)
    {
        $data = $request->validate(['location_id' => 'required|integer', 'variant_id' => 'required|integer', 'quantity' => 'required|integer|min:0|max:2147483647', 'mode' => 'sometimes|in:accumulate,recount']);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $count = DB::transaction(function () use ($inventory, $session_id, $data, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $workspace->editable($inventory);
            $session = $inventory->sessions()->findOrFail($session_id);
            if ($session->status !== 'open') $workspace->fail('inventory_session_id', 'La sesión debe estar abierta para registrar conteos.');
            $location = $workspace->location($inventory, (int) $data['location_id']);
            if ((int) $location->warehouse_id !== (int) $session->warehouse_id) $workspace->fail('location_id', 'La ubicación debe pertenecer al almacén de esta sesión.');
            $previous = InventoryCount::whereHas('session', fn ($q) => $q->where('inventory_id', $inventory->id))
                ->where('location_id', $data['location_id'])->where('variant_id', $data['variant_id']);
            $lastRound = (int) (clone $previous)->max('count_round');
            $round = max(1, $lastRound);
            if (($data['mode'] ?? 'accumulate') === 'recount') {
                if (!$lastRound) $workspace->fail('mode', 'Primero registra un conteo inicial para esta variante y ubicación.');
                $round = $lastRound + 1;
                (clone $previous)->where('status', 'valid')->update(['status' => 'replaced']);
            }
            $count = $session->counts()->create(['location_id' => $data['location_id'], 'variant_id' => $data['variant_id'], 'quantity' => $data['quantity'], 'count_round' => $round, 'status' => 'valid', 'counted_at' => now(), 'user_id' => Auth::id()]);
            $this->resetProgress($inventory, (int) $data['location_id']);
            return $count;
        });
        return response()->json(['data' => $count->load(['location.warehouse', 'variant.product:id,name', 'user:id,name'])], 201);
    }

    public function update(Store $store, Request $request, string $inventory_id, string $session_id, string $count_id, InventoryWorkspace $workspace)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:0|max:2147483647', 'status' => 'required|in:valid,cancelled']);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $count = DB::transaction(function () use ($inventory, $session_id, $count_id, $data, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $workspace->editable($inventory);
            $session = $inventory->sessions()->findOrFail($session_id);
            $count = $session->counts()->findOrFail($count_id);
            if ($session->status !== 'open' || $count->status !== 'valid') $workspace->fail('status', 'Solo se corrigen conteos vigentes de una sesión abierta.');
            if ((int) $count->user_id !== (int) Auth::id()) $workspace->fail('user_id', 'Solo el autor puede corregir este registro.');
            $count->update($data);
            $this->resetProgress($inventory, $count->location_id);
            return $count;
        });
        return responseOk($count->load(['location.warehouse', 'variant.product:id,name', 'user:id,name']));
    }

    private function resetProgress(Inventory $inventory, int $locationId): void
    {
        $progress = $inventory->locationProgress()->firstOrCreate(['location_id' => $locationId], ['status' => 'in_progress', 'started_at' => now()]);
        $progress->update(['status' => 'in_progress', 'started_at' => $progress->started_at ?? now(), 'counted_at' => null, 'verified_at' => null, 'verified_by' => null]);
    }
}
