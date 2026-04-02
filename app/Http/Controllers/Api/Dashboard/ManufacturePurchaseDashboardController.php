<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufacturePurchaseDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $manufacture_id)
    {
        //
        $purchases = $store->manufactures()
            ->findOrFail($manufacture_id)
            ->purchases()
            ->with('supplier', 'items.unit') // Carga las relaciones necesarias para mostrar el nombre del supplier y el unit
            ->get();

            return responseOk($purchases, "se han obtenido correctamente los purchases del manufacture");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     */


    public function store(Store $store, Request $request, $manufacture_id)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'unit_id' => 'required|integer|exists:units,id',
            'price' => 'required|numeric',
            'total' => 'required|numeric',
            'store_id' => 'required|integer|exists:stores,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'observations' => 'nullable|string',
        ]);

        $manufacture = $store->manufactures()
            ->findOrFail($manufacture_id);

        try {

            DB::beginTransaction();

            $purchase = $manufacture->purchases()->create([
                'name' => $validated['name'],
                'quantity' => $validated['quantity'],
                'unit_id' => $validated['unit_id'],
                'price' => $validated['price'],
                'total' => $validated['total'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'observations' => $validated['observations'] ?? null,
                'user_id' => Auth::guard('api')->id(),
                // No agregues store_id ni section_id aquí si es polimórfico
            ]);

            DB::commit();

            return responseOk($purchase->load('supplier'), "se agrego correctamente el purchase en create");
            
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError("Ha sucedido un error interno al crear el purchaseo store x");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
