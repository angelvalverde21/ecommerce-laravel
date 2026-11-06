<?php

namespace App\Services\Inventory;

use App\Models\{Inventory, Location, Variant, Warehouse};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InventoryWorkspace
{
    public function locations(Inventory $inventory): Builder
    {
        $query = Location::where('store_id', $inventory->store_id)->where('is_active', true)->doesntHave('directChildren');
        if ($inventory->scope_type === 'branch') $query->whereHas('warehouse', fn ($q) => $q->where('branch_id', $inventory->inventoriable_id));
        if ($inventory->scope_type === 'warehouse') $query->where('warehouse_id', $inventory->inventoriable_id);
        if ($inventory->scope_type === 'location') {
            $all = Location::where('store_id', $inventory->store_id)->get(['id', 'parent_id']);
            $children = $all->groupBy('parent_id');
            $ids = []; $pending = [(int) $inventory->inventoriable_id];
            while ($pending) {
                $id = array_pop($pending);
                if (isset($ids[$id])) continue;
                $ids[$id] = true;
                foreach ($children->get($id, []) as $child) $pending[] = $child->id;
            }
            $query->whereIn('id', array_keys($ids));
        }
        return $query;
    }

    public function warehouse(Inventory $inventory, int $id): Warehouse
    {
        $query = Warehouse::where('store_id', $inventory->store_id)->where('is_active', true);
        if ($inventory->scope_type === 'branch') $query->where('branch_id', $inventory->inventoriable_id);
        if ($inventory->scope_type === 'warehouse') $query->whereKey($inventory->inventoriable_id);
        if ($inventory->scope_type === 'location') $query->whereHas('locations', fn ($q) => $q->whereKey($inventory->inventoriable_id));
        $warehouse = $query->find($id);
        if (!$warehouse) $this->fail('warehouse_id', 'Selecciona un almacén activo dentro del ámbito del inventario.');
        return $warehouse;
    }

    public function editable(Inventory $inventory): void
    {
        if (in_array($inventory->status, ['completed', 'cancelled'], true)) $this->fail('inventory_id', 'El inventario está finalizado o cancelado.');
        if ($inventory->adjustments()->whereIn('status', ['approved', 'applied'])->exists()) $this->fail('inventory_id', 'Hay ajustes aprobados o aplicados. El conteo ya no puede modificarse.');
    }

    public function variant(Inventory $inventory, int $id): Variant
    {
        $variant = Variant::whereHas('product', fn ($query) => $query->where('store_id', $inventory->store_id))->find($id);
        if (!$variant) $this->fail('variant_id', 'Selecciona una variante de esta empresa.');
        return $variant;
    }

    public function location(Inventory $inventory, int $id): Location
    {
        $location = $this->locations($inventory)->find($id);
        if (!$location) $this->fail('location_id', 'Selecciona una ubicación final dentro del ámbito del inventario.');
        return $location;
    }

    public function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
