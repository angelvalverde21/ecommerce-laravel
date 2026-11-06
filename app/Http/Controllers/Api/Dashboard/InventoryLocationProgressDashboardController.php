<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Inventory, Store};
use App\Services\Inventory\InventoryWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class InventoryLocationProgressDashboardController extends Controller
{
    public function index(Store $store, string $inventory_id)
    {
        return $store->inventories()->findOrFail($inventory_id)->locationProgress()->with(['location.warehouse', 'verifier:id,name'])->orderByDesc('id')->paginate(20);
    }

    public function show(Store $store, string $inventory_id, string $progress_id)
    {
        return responseOk($store->inventories()->findOrFail($inventory_id)->locationProgress()->with(['location.warehouse', 'verifier:id,name'])->findOrFail($progress_id));
    }

    public function store(Store $store, Request $request, string $inventory_id, InventoryWorkspace $workspace)
    {
        $data = $request->validate(['location_id' => 'required|integer']);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $record = DB::transaction(function () use ($inventory, $data, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $workspace->editable($inventory);
            $workspace->location($inventory, (int) $data['location_id']);
            if ($inventory->locationProgress()->where('location_id', $data['location_id'])->exists()) $workspace->fail('location_id', 'La ubicación ya está registrada en este inventario.');
            return $inventory->locationProgress()->create(['location_id' => $data['location_id'], 'status' => 'pending']);
        });
        return response()->json(['data' => $record->load('location.warehouse')], 201);
    }

    public function update(Store $store, Request $request, string $inventory_id, string $progress_id, InventoryWorkspace $workspace)
    {
        $data = $request->validate(['status' => 'required|in:pending,in_progress,counted,verified']);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $record = DB::transaction(function () use ($inventory, $progress_id, $data, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $workspace->editable($inventory);
            $record = $inventory->locationProgress()->findOrFail($progress_id);
            $allowed = ['pending' => ['pending', 'in_progress'], 'in_progress' => ['in_progress', 'counted'], 'counted' => ['counted', 'verified'], 'verified' => ['verified']];
            if (!in_array($data['status'], $allowed[$record->status], true)) $workspace->fail('status', 'Completa las etapas de conteo antes de verificar la ubicación.');
            $changes = $data;
            if ($data['status'] === 'in_progress' && !$record->started_at) $changes['started_at'] = now();
            if ($data['status'] === 'counted' && !$record->counted_at) $changes['counted_at'] = now();
            if ($data['status'] === 'verified' && !$record->verified_at) { $changes['verified_at'] = now(); $changes['verified_by'] = Auth::id(); }
            $record->update($changes);
            return $record;
        });
        return responseOk($record->load(['location.warehouse', 'verifier:id,name']));
    }
}
