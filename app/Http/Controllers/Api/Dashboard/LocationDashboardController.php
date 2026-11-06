<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\{Inventory, InventoryCount, InventoryLocationProgress};
use Illuminate\Database\QueryException;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class LocationDashboardController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $validated = $request->validate(['warehouse_id' => ['sometimes', 'integer'], 'all' => ['sometimes', 'boolean']]);

        // Preserve the existing tree endpoint for inventory consumers.
        if (!isset($validated['warehouse_id'])) {
            return responseOk(Location::where('store_id', $store->id)->whereNull('parent_id')
                ->with('children')->orderBy('name')->get());
        }

        $warehouse = Warehouse::where('store_id', $store->id)->findOrFail($validated['warehouse_id']);
        $query = $warehouse->locations()->with('parent')->orderBy('name')->orderBy('id');

        if ($validated['all'] ?? false) {
            return responseOk($query->get());
        }

        return $query->paginate(20)->withQueryString();
    }

    public function show(Store $store, string $location_id)
    {
        return responseOk(Location::where('store_id', $store->id)->with(['parent', 'children'])->findOrFail($location_id));
    }

    public function store(Store $store, Request $request)
    {
        $location = DB::transaction(function () use ($store, $request) {
            Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            $validated = $this->validated($store, $request);
            return Location::create([...$validated, 'store_id' => $store->id]);
        });

        return response()->json(['data' => $location, 'message' => 'Ubicación creada correctamente.'], 201);
    }

    public function update(Store $store, Request $request, string $location_id)
    {
        $location = Location::where('store_id', $store->id)->findOrFail($location_id);
        $validated = $this->validated($store, $request);
        if ((int) $validated['warehouse_id'] !== $location->warehouse_id) {
            throw ValidationException::withMessages(['warehouse_id' => 'La ubicación debe permanecer en su almacén.']);
        }
        DB::transaction(function () use ($store, $location, $validated) {
            Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            $parentId = $validated['parent_id'] ?? null;
            $visited = [];
            while ($parentId !== null) {
                if ((int) $parentId === $location->id || isset($visited[$parentId])) {
                    throw ValidationException::withMessages(['parent_id' => 'Selecciona una ubicación superior que no sea esta ubicación ni una de sus sububicaciones.']);
                }
                $visited[$parentId] = true;
                $parent = Location::where('store_id', $store->id)->where('warehouse_id', $location->warehouse_id)->findOrFail($parentId);
                $parentId = $parent->parent_id;
            }
            $location->update($validated);
        });
        return responseOk($location->fresh(), 'Ubicación actualizada correctamente.');
    }

    public function destroy(Store $store, string $location_id)
    {
        try {
            DB::transaction(function () use ($store, $location_id) {
                Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
                $location = Location::where('store_id', $store->id)->lockForUpdate()->findOrFail($location_id);
                if ($location->directChildren()->exists()) {
                    abort(409, 'No se puede eliminar esta ubicación porque tiene sububicaciones. Elimina primero sus sububicaciones.');
                }
                $usedByInventory = Inventory::where('inventoriable_type', $location->getMorphClass())->where('inventoriable_id', $location->id)->exists();
                if ($usedByInventory || InventoryCount::where('location_id', $location->id)->exists() || InventoryLocationProgress::where('location_id', $location->id)->exists()) {
                    abort(409, 'No se puede eliminar esta ubicación porque tiene inventarios o conteos asociados. Puedes marcarla como inactiva.');
                }
                $location->delete();
            });
        } catch (QueryException $exception) {
            if (in_array($exception->errorInfo[0] ?? null, ['23000', '23503'], true)) {
                abort(409, 'No se puede eliminar esta ubicación porque tiene registros asociados. Puedes marcarla como inactiva.');
            }
            throw $exception;
        }
        return response()->noContent();
    }

    private function validated(Store $store, Request $request, ?int $id = null): array
    {
        return $request->validate([
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('store_id', $store->id)],
            'parent_id' => ['nullable', 'integer', Rule::exists('locations', 'id')
                ->where('store_id', $store->id)->where('warehouse_id', $request->input('warehouse_id'))],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
