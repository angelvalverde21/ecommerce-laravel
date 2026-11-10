<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryCount;
use App\Models\Location;
use App\Models\Store;
use App\Services\Inventory\InventoryWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryCountDashboardController extends Controller
{
    public function index(Store $store, Request $request, string $inventory_id, string $session_id)
    {
        $data = $request->validate([
            'variant_id' => 'nullable|integer|min:1',
            'location_id' => 'required_if:view,rack|nullable|integer|min:1',
            'grouped' => 'sometimes|boolean',
            'view' => 'sometimes|in:rack,rack-summary',
        ]);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $query = $inventory->sessions()->findOrFail($session_id)->counts()
            ->when(isset($data['variant_id']), fn ($query) => $query->where('variant_id', $data['variant_id']))
            ->when(isset($data['location_id']), fn ($query) => $query->where('location_id', $data['location_id']));
        if (($data['view'] ?? null) === 'rack-summary') {
            $totals = $query->where('inventory_counts.status', 'valid')
                ->select('location_id')
                ->selectRaw('SUM(quantity) AS units, COUNT(*) AS captures, COUNT(DISTINCT variant_id) AS variants')
                ->groupBy('location_id')->orderBy('location_id')->get()
                ->map(fn ($row) => [
                    'location_id' => (int) $row->location_id,
                    'units' => (int) $row->units,
                    'captures' => (int) $row->captures,
                    'variants' => (int) $row->variants,
                ]);

            return responseOk($totals);
        }
        if (($data['view'] ?? null) === 'rack') {
            $valid = (clone $query)->where('inventory_counts.status', 'valid');
            $totals = (clone $valid)->selectRaw('COALESCE(SUM(quantity), 0) AS units, COUNT(*) AS captures, COUNT(DISTINCT variant_id) AS variants')->first();
            $rack = $query->with([
                'variant.product.image',
                'variant.variant_option_values.option',
                'variant.variant_option_values.optionValue',
                'user:id,name',
            ])->orderByDesc('counted_at')->orderByDesc('id')->paginate(20)->withQueryString();

            return response()->json([
                ...$rack->toArray(),
                'summary' => [
                    'units' => (int) $totals->units,
                    'captures' => (int) $totals->captures,
                    'variants' => (int) $totals->variants,
                ],
            ]);
        }
        if ($request->boolean('grouped')) {
            $groups = (clone $query)->select('variant_id')->selectRaw('MAX(id) as latest_id')->groupBy('variant_id')->orderByDesc('latest_id')->paginate(20)->withQueryString();
            $ids = $groups->getCollection()->pluck('variant_id');
            $captures = (clone $query)->whereIn('variant_id', $ids)->with(['location.warehouse', 'location.parent:id,name', 'variant.product:id,name', 'variant.variant_option_values.option', 'variant.variant_option_values.optionValue', 'user:id,name'])->orderByDesc('counted_at')->orderByDesc('id')->get();

            return response()->json($groups->setCollection($captures));
        }
        $result = $query->with(['location.warehouse', 'location.parent:id,name', 'variant.product:id,name', 'variant.variant_option_values.option', 'variant.variant_option_values.optionValue', 'user:id,name'])->orderByDesc('counted_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $result = $result->toArray();
        if (isset($data['variant_id'])) {
            $result['variant_counted_quantity'] = (int) $inventory->counts()->where('variant_id', $data['variant_id'])->where('inventory_counts.status', 'valid')->sum('quantity');
            if (isset($data['location_id'])) {
                $result['other_locations'] = $this->variantOtherLocations($inventory, (int) $data['variant_id'], (int) $data['location_id']);
            }
        }

        return response()->json($result);
    }

    private function variantOtherLocations(Inventory $inventory, int $variantId, int $locationId): array
    {
        $totals = InventoryCount::query()
            ->whereHas('session', fn ($query) => $query->where('inventory_id', $inventory->id))
            ->where('variant_id', $variantId)
            ->where('inventory_counts.status', 'valid')
            ->where('inventory_counts.location_id', '<>', $locationId)
            ->select('inventory_counts.location_id')
            ->selectRaw('SUM(quantity) AS quantity')
            ->groupBy('inventory_counts.location_id')
            ->havingRaw('SUM(quantity) > 0')
            ->with('location.warehouse.branch')
            ->orderBy('inventory_counts.location_id')
            ->get();

        if ($totals->isEmpty()) {
            return [];
        }

        $locations = Location::where('store_id', $inventory->store_id)->get(['id', 'parent_id', 'name'])->keyBy('id');

        return $totals->map(function ($total) use ($locations) {
            $path = [];
            $visited = [];
            $node = $locations->get($total->location_id);
            while ($node && ! isset($visited[$node->id])) {
                $visited[$node->id] = true;
                array_unshift($path, $node->name);
                $node = $locations->get($node->parent_id);
            }

            return [
                'location_id' => (int) $total->location_id,
                'location_path' => implode(' / ', $path) ?: 'Ubicación #'.$total->location_id,
                'warehouse_id' => $total->location?->warehouse_id,
                'warehouse_name' => $total->location?->warehouse?->name,
                'branch_name' => $total->location?->warehouse?->branch?->name,
                'quantity' => (int) $total->quantity,
            ];
        })->all();
    }

    public function show(Store $store, string $inventory_id, string $session_id, string $count_id)
    {
        return responseOk($store->inventories()->findOrFail($inventory_id)->sessions()->findOrFail($session_id)->counts()
            ->with(['location.warehouse', 'location.parent:id,name', 'variant.product:id,name', 'variant.variant_option_values.option', 'variant.variant_option_values.optionValue', 'user:id,name'])->findOrFail($count_id));
    }

    public function store(Store $store, Request $request, string $inventory_id, string $session_id, InventoryWorkspace $workspace)
    {
        $data = $request->validate(['location_id' => 'required|integer', 'variant_id' => 'required|integer', 'quantity' => 'required|integer|min:0|max:2147483647', 'mode' => 'sometimes|in:accumulate,recount']);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $count = DB::transaction(function () use ($inventory, $session_id, $data, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $workspace->editable($inventory);
            $session = $inventory->sessions()->findOrFail($session_id);
            if ($session->status !== 'open') {
                $workspace->fail('inventory_session_id', 'La sesión debe estar abierta para registrar conteos.');
            }
            $location = $workspace->location($inventory, (int) $data['location_id']);
            if ((int) $location->warehouse_id !== (int) $session->warehouse_id) {
                $workspace->fail('location_id', 'La ubicación debe pertenecer al almacén de esta sesión.');
            }
            $previous = InventoryCount::whereHas('session', fn ($q) => $q->where('inventory_id', $inventory->id))
                ->where('location_id', $data['location_id'])->where('variant_id', $data['variant_id']);
            $lastRound = (int) (clone $previous)->max('count_round');
            $round = max(1, $lastRound);
            if (($data['mode'] ?? 'accumulate') === 'recount') {
                if (! $lastRound) {
                    $workspace->fail('mode', 'Primero registra un conteo inicial para esta variante y ubicación.');
                }
                $round = $lastRound + 1;
                (clone $previous)->where('status', 'valid')->update(['status' => 'replaced']);
            }
            $count = $session->counts()->create(['location_id' => $data['location_id'], 'variant_id' => $data['variant_id'], 'quantity' => $data['quantity'], 'count_round' => $round, 'status' => 'valid', 'counted_at' => now(), 'user_id' => Auth::id()]);
            $this->resetProgress($inventory, (int) $data['location_id']);

            return $count;
        });

        return response()->json(['data' => $count->load(['location.warehouse', 'location.parent:id,name', 'variant.product:id,name', 'variant.variant_option_values.option', 'variant.variant_option_values.optionValue', 'user:id,name'])], 201);
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
            if ($session->status !== 'open' || $count->status !== 'valid') {
                $workspace->fail('status', 'Solo se corrigen conteos vigentes de una sesión abierta.');
            }
            if ((int) $count->user_id !== (int) Auth::id()) {
                $workspace->fail('user_id', 'Solo el autor puede corregir este registro.');
            }
            $count->update($data);
            $this->resetProgress($inventory, $count->location_id);

            return $count;
        });

        return responseOk($count->load(['location.warehouse', 'location.parent:id,name', 'variant.product:id,name', 'variant.variant_option_values.option', 'variant.variant_option_values.optionValue', 'user:id,name']));
    }

    public function destroy(Store $store, string $inventory_id, string $session_id, string $count_id, InventoryWorkspace $workspace)
    {
        $inventory = $store->inventories()->findOrFail($inventory_id);
        DB::transaction(function () use ($inventory, $session_id, $count_id, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $workspace->editable($inventory);
            $session = $inventory->sessions()->lockForUpdate()->findOrFail($session_id);
            $count = $session->counts()->lockForUpdate()->findOrFail($count_id);
            if ($session->status !== 'open' || $count->status !== 'valid') {
                $workspace->fail('status', 'Solo se eliminan capturas vigentes de una sesión abierta.');
            }
            abort_unless((int) $count->user_id === (int) Auth::id(), 403, 'Solo el autor puede eliminar esta captura.');
            $locationId = $count->location_id;
            $count->delete();
            if ($inventory->counts()->where('location_id', $locationId)->where('inventory_counts.status', 'valid')->exists()) {
                $this->resetProgress($inventory, $locationId);
            } else {
                $inventory->locationProgress()->where('location_id', $locationId)->update([
                    'status' => 'pending', 'started_at' => null, 'counted_at' => null, 'verified_at' => null, 'verified_by' => null,
                ]);
            }
        });

        return response()->noContent();
    }

    private function resetProgress(Inventory $inventory, int $locationId): void
    {
        $progress = $inventory->locationProgress()->firstOrCreate(['location_id' => $locationId], ['status' => 'in_progress', 'started_at' => now()]);
        $progress->update(['status' => 'in_progress', 'started_at' => $progress->started_at ?? now(), 'counted_at' => null, 'verified_at' => null, 'verified_by' => null]);
    }
}
