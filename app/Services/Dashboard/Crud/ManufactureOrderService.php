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

        $manufactureOrders = $store->manufactures()
            ->with(['user', 'supplier'])
            ->where('type', 'order')
            ->get();

        return $manufactureOrders;
    }

    public function store(Store $store, Request $request)
    {
        //
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'manufacture_start' => 'nullable|date',
            'manufacture_end' => 'nullable|date|after_or_equal:manufacture_start',
        ]);

        $manufacture = $store->manufactures()->create(
            [
                'name' => $data['name'],
                'user_id' => Auth::id(),
                'type' => 'order',
                'supplier_id' => $data['supplier_id'],
                'manufacture_start' => $data['manufacture_start'],
                'manufacture_end' => $data['manufacture_end'],
            ]
        );

        return $manufacture;
    }

    public function update(Request $request, Store $store, $manufacture_id)
    {
        //

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'manufacture_start' => 'nullable|date',
                'manufacture_end' => 'nullable|date|after_or_equal:manufacture_start',
            ]);

            $manufacture = $store->manufactures()->findOrFail($manufacture_id);

            $manufacture->update($data);

            return $manufacture;

    }
}
