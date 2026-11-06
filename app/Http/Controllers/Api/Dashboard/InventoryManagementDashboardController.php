<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Inventory, Store, Branch, Warehouse, Location};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryManagementDashboardController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:255', 'status' => 'nullable|in:draft,in_progress,completed,cancelled']);
        return $store->inventories()->with(['creator:id,name', 'inventoriable'])->withSum(['counts as counted_quantity' => fn ($query) => $query->where('inventory_counts.status', 'valid')], 'quantity')
            ->withCount(['sessions', 'sessions as open_sessions_count' => fn ($query) => $query->where('status', 'open')])
            ->when($filters['search'] ?? null, fn ($query, $term) => $query->where('name', 'like', '%'.$term.'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();
    }

    public function options(Store $store)
    {
        $operators = $store->users()->select('users.id', 'users.name')->orderBy('name')->get();
        if (!$operators->contains('id', Auth::id())) $operators->push(Auth::user()->only(['id', 'name']));
        return responseOk([
            'branches' => Branch::where('store_id', $store->id)->orderBy('name')->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::where('store_id', $store->id)->with('branch:id,name')->orderBy('name')->get(['id', 'branch_id', 'name', 'code']),
            'locations' => Location::where('store_id', $store->id)->with('warehouse:id,name')->orderBy('name')->get(['id', 'warehouse_id', 'name', 'code']),
            'operators' => $operators->values(), 'current_user_id' => Auth::id(),
        ]);
    }

    public function show(Store $store, string $inventory_id)
    {
        return responseOk($store->inventories()->with(['creator:id,name', 'inventoriable'])->withSum(['counts as counted_quantity' => fn ($query) => $query->where('inventory_counts.status', 'valid')], 'quantity')->withCount(['sessions', 'locationProgress', 'adjustments'])->findOrFail($inventory_id));
    }

    public function store(Store $store, Request $request)
    {
        $data = $this->validated($store, $request);
        $inventory = $store->inventories()->create([...$data, 'status' => 'draft', 'created_user_id' => Auth::id()]);
        return response()->json(['data' => $inventory->load('inventoriable'), 'message' => 'Inventario creado.'], 201);
    }

    public function update(Store $store, Request $request, string $inventory_id)
    {
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $data = $this->validated($store, $request);
        $request->validate(['status' => 'required|in:draft,in_progress,completed,cancelled']);
        $inventory = DB::transaction(function () use ($inventory, $request, $data) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $transitions = ['draft' => ['draft', 'in_progress', 'cancelled'], 'in_progress' => ['in_progress', 'completed', 'cancelled'], 'completed' => ['completed'], 'cancelled' => ['cancelled']];
            $status = $request->input('status');
            if (!in_array($status, $transitions[$inventory->status], true)) throw ValidationException::withMessages(['status' => 'No se puede volver a un estado anterior.']);
            if (($inventory->sessions()->exists() || $inventory->locationProgress()->exists() || $inventory->adjustments()->exists()) && ($inventory->inventoriable_type !== $data['inventoriable_type'] || (int) $inventory->inventoriable_id !== (int) $data['inventoriable_id'])) {
                throw ValidationException::withMessages(['inventoriable_id' => 'El ámbito no se puede cambiar cuando existen sesiones.']);
            }
            if ($status === 'completed' && $inventory->adjustments()->whereIn('status', ['pending', 'approved'])->exists()) throw ValidationException::withMessages(['status' => 'Aplica o rechaza los ajustes pendientes antes de finalizar.']);
            if ($status === 'completed' && $inventory->sessions()->where('status', 'open')->exists()) throw ValidationException::withMessages(['status' => 'Cierra o cancela las sesiones abiertas antes de finalizar el inventario.']);
            if ($status === 'in_progress' && !$inventory->started_at) $data['started_at'] = now();
            if ($status === 'completed' && !$inventory->completed_at) $data['completed_at'] = now();
            if ($status === 'cancelled') $inventory->sessions()->where('status', 'open')->update(['status' => 'cancelled', 'ended_at' => now()]);
            $inventory->update([...$data, 'status' => $status]);
            return $inventory;
        });
        return responseOk($inventory->load('inventoriable'), 'Inventario actualizado.');
    }

    private function validated(Store $store, Request $request): array
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'comment' => 'nullable|string|max:5000', 'inventoriable_type' => ['nullable', Rule::in(['branch', 'warehouse', 'location'])], 'inventoriable_id' => 'nullable|integer']);
        $types = ['branch' => Branch::class, 'warehouse' => Warehouse::class, 'location' => Location::class];
        $type = $data['inventoriable_type'] ?? null;
        if ($type) {
            $target = $types[$type]::where('store_id', $store->id)->find($data['inventoriable_id'] ?? null);
            if (!$target) throw ValidationException::withMessages(['inventoriable_id' => 'Selecciona un registro de esta empresa.']);
            $data['inventoriable_type'] = $target->getMorphClass();
        } else { $data['inventoriable_type'] = null; $data['inventoriable_id'] = null; }
        return $data;
    }
}
