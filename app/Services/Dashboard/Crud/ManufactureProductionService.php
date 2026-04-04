<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Manufacture;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufactureProductionService
{

    public function index(Store $store)
    {

        $manufactureOrders = $store->manufactures()
             ->withFinancialSummary()
            ->get();

        return $manufactureOrders;
    }

    public function store(Store $store, Request $request)
    {
        //
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $manufacture = $store->manufactures()->create(
            [
                'name' => $data['name'],
                'user_id' => Auth::id(),
                'type' => 'production',
            ]
        );

        return $manufacture;
    }

    public function update(Request $request, Store $store, $manufacture_id)
    {
        //

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'manufacture_start' => 'nullable|date',
            'manufacture_end' => 'nullable|date|after_or_equal:manufacture_start',
        ]);

        $manufacture = $store->manufactures()->findOrFail($manufacture_id);

        $manufacture->update($data);

        return $manufacture;
    }


}
