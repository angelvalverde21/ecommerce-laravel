<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufactureOrderService
{

    public function index(Store $store)
    {

        $manufactureOrders = $store->manufactures()->where('type', 'order')
            ->withFinancialSummary()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return app(\App\Services\Collections\ManufactureCollectionLabels::class)->attach($manufactureOrders, $store->id);
    }

    public function store(Store $store, Request $request)
    {
        //
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_address_id' => 'nullable|integer',
            'manufacture_start' => 'nullable|date',
            'manufacture_end' => 'nullable|date|after_or_equal:manufacture_start',
        ]);

        $data['supplier_address_id'] = app(\App\Services\Dashboard\Supplier\SupplierAddressService::class)
            ->resolve($store, $data['supplier_id'] ?? null, $data['supplier_address_id'] ?? null);

        $manufacture = $store->manufactures()->create(
            [
                'name' => $data['name'],
                'user_id' => Auth::id(),
                'type' => 'order',
                'supplier_id' => $data['supplier_id'] ?? null,
                'supplier_address_id' => $data['supplier_address_id'],
                'manufacture_start' => $data['manufacture_start'] ?? null,
                'manufacture_end' => $data['manufacture_end'] ?? null,
            ]
        );

        return $manufacture;
    }

    public function update(Request $request, Store $store, $manufacture_id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_address_id' => 'nullable|integer',
            'manufacture_start' => 'nullable|date',
            'manufacture_end' => 'nullable|date|after_or_equal:manufacture_start',
        ]);
        $data['supplier_address_id'] = app(\App\Services\Dashboard\Supplier\SupplierAddressService::class)
            ->resolve($store, $data['supplier_id'] ?? null, $data['supplier_address_id'] ?? null);
        $manufacture = $store->manufactures()->where('type', 'order')->findOrFail($manufacture_id);
        $manufacture->update($data);
        return $manufacture;
    }
}
