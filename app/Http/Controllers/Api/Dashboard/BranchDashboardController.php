<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Store;
use App\Support\AutomaticOrganizationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchDashboardController extends Controller
{
    public function index(Store $store)
    {
        $branches = Branch::where('store_id', $store->id)->withCount('warehouses')
            ->orderByDesc('is_main')->orderBy('name')->orderBy('id')->paginate(20);

        return response()->json([
            ...$branches->toArray(),
            'store' => $store->only(['id', 'name', 'slug']),
        ]);
    }

    public function addresses(Store $store)
    {
        return responseOk($store->addresses()->orderBy('primary')->get(['id', 'name', 'primary']));
    }

    public function show(Store $store, string $branch_id)
    {
        return responseOk(Branch::where('store_id', $store->id)->with('address')->withCount('warehouses')->findOrFail($branch_id));
    }

    public function store(Store $store, Request $request)
    {
        $validated = $this->validated($store, $request);

        $branch = DB::transaction(function () use ($store, $validated) {
            Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            if ($validated['is_main'] ?? false) {
                Branch::where('store_id', $store->id)->where('is_main', true)->update(['is_main' => false]);
            }

            return Branch::create([...$validated, 'store_id' => $store->id]);
        });

        return response()->json(['data' => $branch, 'message' => 'Sucursal creada correctamente.'], 201);
    }

    public function update(Store $store, Request $request, string $branch_id)
    {
        $branch = Branch::where('store_id', $store->id)->findOrFail($branch_id);
        $validated = $this->validated($store, $request, $branch->id);
        DB::transaction(function () use ($store, $branch, $validated) {
            Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            if ($validated['is_main'] ?? false) {
                Branch::where('store_id', $store->id)->where('id', '!=', $branch->id)
                    ->where('is_main', true)->update(['is_main' => false]);
            }
            $branch->update($validated);
        });
        return responseOk($branch->fresh(), 'Sucursal actualizada correctamente.');
    }

    private function validated(Store $store, Request $request, ?int $id = null): array
    {
        AutomaticOrganizationCode::fillMissing($request);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('branches')->where('store_id', $store->id)->ignore($id)],
            'address_id' => ['nullable', 'integer', Rule::exists('addresses', 'id')
                ->where('addressable_type', $store->getMorphClass())->where('addressable_id', $store->id)],
            'is_main' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
