<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Store;
use App\Models\Warehouse;
use App\Support\AutomaticOrganizationCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseDashboardController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $validated = $request->validate(['branch_id' => ['required', 'integer']]);
        $branch = Branch::where('store_id', $store->id)->findOrFail($validated['branch_id']);

        return $branch->warehouses()->with('branch.address')->withCount('locations')
            ->orderBy('name')->orderBy('id')->paginate(20)->withQueryString();
    }

    public function show(Store $store, string $warehouse_id)
    {
        return responseOk(Warehouse::where('store_id', $store->id)->with('branch.address')->findOrFail($warehouse_id));
    }

    public function store(Store $store, Request $request)
    {
        $validated = $this->validated($store, $request);

        $warehouse = Warehouse::create([...$validated, 'store_id' => $store->id]);

        return response()->json(['data' => $warehouse->load('branch.address'), 'message' => 'Almacén creado correctamente.'], 201);
    }

    public function update(Store $store, Request $request, string $warehouse_id)
    {
        $warehouse = Warehouse::where('store_id', $store->id)->findOrFail($warehouse_id);
        $validated = $this->validated($store, $request, $warehouse->id);
        $warehouse->update($validated);
        return responseOk($warehouse->fresh()->load('branch.address'), 'Almacén actualizado correctamente.');
    }

    private function validated(Store $store, Request $request, ?int $id = null): array
    {
        AutomaticOrganizationCode::fillMissing($request);

        return $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('store_id', $store->id)],
            'name' => ['required', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses')->where('store_id', $store->id)->ignore($id)],
            'address' => ['prohibited'],
            'address_id' => ['prohibited'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
