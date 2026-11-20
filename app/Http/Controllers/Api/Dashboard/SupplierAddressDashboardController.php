<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\Supplier\SupplierAddressService;

class SupplierAddressDashboardController extends Controller
{
    public function index(Store $store, int $supplier_id, SupplierAddressService $service)
    {
        $supplier = $service->supplier($store, $supplier_id);
        $service->ensureDefault($supplier);
        return responseOk($supplier->addresses()->where('status', 1)->with('district')
            ->orderByDesc('is_default')->orderBy('id')->get());
    }

    public function setDefault(Store $store, int $supplier_id, int $address_id, SupplierAddressService $service)
    {
        $supplier = $service->supplier($store, $supplier_id);
        $address = $supplier->addresses()->findOrFail($address_id);
        $service->makeDefault($supplier, $address);
        return responseOk($supplier->addresses()->with('district')->orderByDesc('is_default')->orderBy('id')->get());
    }
}
