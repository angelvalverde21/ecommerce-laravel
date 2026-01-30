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
    public function index(Store $store)
    {
        //
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
        //

        $typeMap = [
            'manufacture' => \App\Models\Manufacture::class,
            // Agrega más modelos según tu caso
        ];


        $resp = $request->all();


        // $rules = $this->rules;

        // $this->validate($rules);


        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'unit_id' => 'required|integer|exists:units,id',
            'price' => 'required|numeric',
            'total' => 'required|numeric',
            'section_id' => 'required|integer|exists:sections,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'observations' => 'nullable|string',
        ]);


        $modelClass = $typeMap[$validated['purchaseable_type']];

        // Obtener nombre tabla para validar existencia
        $tableName = (new $modelClass)->getTable();

        $parentModel = $modelClass::where('store_id', $store->id)->findOrFail($validated['purchaseable_id']);

        try {


            DB::beginTransaction();

            $purchase = $parentModel->purchases()->create([
                'name' => $validated['name'],
                'quantity' => $validated['quantity'],
                'unit_id' => $validated['unit_id'],
                'price' => $validated['price'],
                'total' => $validated['total'],
                'section_id' => $validated['section_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'observations' => $validated['observations'] ?? null,
                'user_id' => Auth::guard('api')->id(),
                // No agregues store_id ni section_id aquí si es polimórfico
            ]);


            // return redirect()->route('erp.purchases.edit', ['store' => $this->store, 'purchase' => $purchase]);

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
