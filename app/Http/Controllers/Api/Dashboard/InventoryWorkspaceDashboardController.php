<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Store;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryWorkspace;
use Illuminate\Http\Request;

class InventoryWorkspaceDashboardController extends Controller
{
    public function options(Store $store, string $inventory_id, InventoryWorkspace $workspace)
    {
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $warehouses = Warehouse::where('store_id', $store->id)->where('is_active', true);
        if ($inventory->scope_type === 'branch') {
            $warehouses->where('branch_id', $inventory->inventoriable_id);
        }
        if ($inventory->scope_type === 'warehouse') {
            $warehouses->whereKey($inventory->inventoriable_id);
        }
        if ($inventory->scope_type === 'location') {
            $warehouses->whereHas('locations', fn ($query) => $query->whereKey($inventory->inventoriable_id)->where('store_id', $store->id));
        }
        $warehouses = $warehouses->orderBy('name')->get(['id', 'name', 'subtitle', 'branch_id']);
        $branches = Branch::where('store_id', $store->id)->where('is_active', true);
        if ($inventory->scope_type === 'branch') {
            $branches->whereKey($inventory->inventoriable_id);
        }
        if (in_array($inventory->scope_type, ['warehouse', 'location'], true)) {
            $branches->whereIn('id', $warehouses->pluck('branch_id'));
        }

        return responseOk([
            'current_user_id' => (int) \Illuminate\Support\Facades\Auth::id(),
            'recording_locked' => $inventory->adjustments()->whereIn('status', ['approved', 'applied'])->exists(),
            'branches' => $branches->orderBy('name')->get(['id', 'name']),
            'warehouses' => $warehouses,
            'location_tree' => $workspace->locationHierarchy($inventory)->orderBy('name')->get(['id', 'warehouse_id', 'parent_id', 'name', 'code', 'is_active']),
            'locations' => $workspace->locations($inventory)->with(['warehouse:id,name,subtitle,branch_id', 'warehouse.branch:id,name,store_id', 'parent:id,name'])->orderBy('name')->get(),
        ]);
    }

    public function variants(Store $store, Request $request, string $inventory_id)
    {
        $store->inventories()->findOrFail($inventory_id);
        $data = $request->validate(['search' => 'required|string|max:255']);
        $term = trim($data['search']);
        if ($term === '') {
            return responseOk([]);
        }
        $variants = Variant::whereHas('product', fn ($q) => $q->where('store_id', $store->id))
            ->where(function ($q) use ($term) {
                $q->where('sku', 'like', '%'.$term.'%')->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$term.'%'));
                if (ctype_digit($term)) {
                    $q->orWhere('id', (int) $term);
                }
            })->with('product:id,name')->orderBy('sku')->orderBy('id')->limit(20)->get();

        return responseOk($variants);
    }
}
