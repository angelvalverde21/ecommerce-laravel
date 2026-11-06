<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Inventory, InventoryCount, Store, Variant};
use App\Services\Inventory\InventoryWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class InventoryAdjustmentDashboardController extends Controller
{
    public function index(Store $store, string $inventory_id)
    {
        return $store->inventories()->findOrFail($inventory_id)->adjustments()->with(['variant.product:id,name', 'approver:id,name'])->orderByDesc('id')->paginate(20);
    }

    public function show(Store $store, string $inventory_id, string $adjustment_id)
    {
        return responseOk($store->inventories()->findOrFail($inventory_id)->adjustments()->with(['variant.product:id,name', 'approver:id,name'])->findOrFail($adjustment_id));
    }

    public function store(Store $store, Request $request, string $inventory_id, InventoryWorkspace $workspace)
    {
        $data = $request->validate(['variant_id' => 'required|integer', 'comment' => 'nullable|string|max:5000']);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $adjustment = DB::transaction(function () use ($inventory, $data, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $workspace->editable($inventory);
            if ($inventory->inventoriable_type !== null) $workspace->fail('inventory_id', 'El inventario es parcial. Para ajustar el stock global se necesita un inventario de toda la empresa o saldos por ubicacion.');
            if ($inventory->adjustments()->where('variant_id', $data['variant_id'])->exists()) $workspace->fail('variant_id', 'La variante ya tiene un ajuste. Revisa el registro existente.');
            $variant = $workspace->variant($inventory, (int) $data['variant_id']);
            $snapshot = $this->snapshot($inventory, $variant, $workspace);
            return $inventory->adjustments()->create([...$snapshot, 'variant_id' => $variant->id, 'comment' => $data['comment'] ?? null, 'status' => 'pending']);
        });
        return response()->json(['data' => $adjustment->load('variant.product:id,name')], 201);
    }

    public function update(Store $store, Request $request, string $inventory_id, string $adjustment_id, InventoryWorkspace $workspace)
    {
        $data = $request->validate(['comment' => 'nullable|string|max:5000', 'refresh' => 'sometimes|boolean']);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $adjustment = DB::transaction(function () use ($inventory, $adjustment_id, $data, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $adjustment = $inventory->adjustments()->findOrFail($adjustment_id);
            if ($adjustment->status !== 'pending' && !(($data['refresh'] ?? false) && in_array($adjustment->status, ['approved', 'rejected'], true))) $workspace->fail('status', 'Solo se editan ajustes pendientes o se recalculan ajustes no aplicados.');
            if (in_array($inventory->status, ['completed', 'cancelled'], true)) $workspace->fail('inventory_id', 'El inventario está finalizado o cancelado.');
            $changes = ['comment' => $data['comment'] ?? null];
            if ($data['refresh'] ?? false) $changes = [...$changes, 'status' => 'pending', 'approved_by' => null, 'approved_at' => null];
            if ($data['refresh'] ?? false) $changes = [...$changes, ...$this->snapshot($inventory, $workspace->variant($inventory, $adjustment->variant_id), $workspace)];
            $adjustment->update($changes);
            return $adjustment;
        });
        return responseOk($adjustment->load(['variant.product:id,name', 'approver:id,name']));
    }

    public function transition(Store $store, Request $request, string $inventory_id, string $adjustment_id, string $action, InventoryWorkspace $workspace)
    {
        abort_unless(in_array($action, ['approve', 'reject', 'apply'], true), 404);
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $adjustment = DB::transaction(function () use ($inventory, $adjustment_id, $action, $workspace) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $adjustment = $inventory->adjustments()->findOrFail($adjustment_id);
            if ($action === 'apply' && $adjustment->status === 'applied') return $adjustment;
            if (in_array($inventory->status, ['completed', 'cancelled'], true)) $workspace->fail('inventory_id', 'El inventario está finalizado o cancelado.');
            if ($action === 'reject') {
                if (!in_array($adjustment->status, ['pending', 'approved'], true)) $workspace->fail('status', 'El ajuste ya no se puede rechazar.');
                $adjustment->update(['status' => 'rejected']);
                return $adjustment;
            }
            if ($inventory->sessions()->where('status', 'open')->exists()) $workspace->fail('status', 'Cierra las sesiones de conteo antes de aprobar o aplicar ajustes.');
            if ($inventory->locationProgress()->where('status', '!=', 'verified')->exists()) $workspace->fail('status', 'Verifica las ubicaciones registradas antes de aprobar o aplicar ajustes.');
            if ($inventory->inventoriable_type !== null) $workspace->fail('inventory_id', 'Un conteo parcial no se puede aplicar al stock global.');
            $verifiedLocations = $inventory->locationProgress()->where('status', 'verified')->select('location_id');
            if ($workspace->locations($inventory)->whereNotIn('id', $verifiedLocations)->exists()) $workspace->fail('status', 'Registra y verifica todas las ubicaciones finales antes de aprobar o aplicar ajustes.');
            if ($action === 'approve') {
                if ($adjustment->status !== 'pending') $workspace->fail('status', 'Solo se aprueban ajustes pendientes.');
                $variant = $workspace->variant($inventory, $adjustment->variant_id);
                $snapshot = $this->snapshot($inventory, $variant, $workspace);
                if ($snapshot['system_quantity'] !== $adjustment->system_quantity || $snapshot['counted_quantity'] !== $adjustment->counted_quantity) $workspace->fail('status', 'Los conteos o el stock cambiaron. Actualiza el cálculo antes de aprobar.');
                $adjustment->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
                return $adjustment;
            }
            if ($adjustment->status !== 'approved') $workspace->fail('status', 'Aprueba el ajuste antes de aplicarlo.');
            $variant = Variant::whereKey($adjustment->variant_id)->lockForUpdate()->firstOrFail();
            if ((int) $variant->stock !== $adjustment->system_quantity) $workspace->fail('status', 'El stock cambió después de la revisión. No se aplicó el ajuste.');
            if ($adjustment->difference !== 0) {
                $variant->update(['stock' => $adjustment->counted_quantity]);
                $adjustment->kardexes()->create(['store_id' => $inventory->store_id, 'product_id' => $variant->product_id, 'variant_id' => $variant->id, 'quantity' => abs($adjustment->difference), 'direction' => $adjustment->difference > 0 ? 'in' : 'out', 'comment' => 'Ajuste de inventario #'.$inventory->id.' / ajuste #'.$adjustment->id]);
            }
            $adjustment->update(['status' => 'applied', 'applied_at' => now()]);
            return $adjustment;
        });
        return responseOk($adjustment->load(['variant.product:id,name', 'approver:id,name']));
    }

    private function snapshot(Inventory $inventory, Variant $variant, InventoryWorkspace $workspace): array
    {
        $counts = InventoryCount::whereHas('session', fn ($q) => $q->where('inventory_id', $inventory->id))->where('variant_id', $variant->id)->where('status', 'valid');
        if (!(clone $counts)->exists()) $workspace->fail('variant_id', 'La variante no tiene conteos físicos vigentes en este inventario.');
        $quantity = (int) $counts->sum('quantity');
        if ($quantity > 2147483647) $workspace->fail('variant_id', 'La suma de cantidades excede el límite permitido.');
        return ['system_quantity' => (int) $variant->stock, 'counted_quantity' => $quantity, 'difference' => $quantity - (int) $variant->stock];
    }
}
