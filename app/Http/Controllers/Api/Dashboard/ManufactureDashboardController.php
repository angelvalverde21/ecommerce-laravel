<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufactureDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
        try {

            $manufactures = $store->manufactures()
                ->with(['user'])
                ->withSum('manufactureVariants as sum_products', 'quantity')
                ->withSum('purchases as sum_purchases', 'total')
                ->get();

            return responseOk($manufactures, "Listado de manufacturas obtenido correctamente");
        } catch (\Throwable $th) {

            Log::info($th);
            return responseError("Error al obtener el listado de manufacturas");
        }
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
    public function store(Store $store, Request $request)
    {
        //

        try {

            DB::beginTransaction();

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'quantity_total' => 'nullable|integer',
                'budget' => 'nullable|numeric',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'type' => 'required|string|max:255',
            ]);

            $manufacture = $store->manufactures()->create(
                [
                    'name' => $data['name'],
                    'quantity_total' => $data['quantity_total'] ?? 0,
                    'budget' => $data['budget'] ?? 0.00,
                    'user_id' => Auth::id(),
                    'type' => $data['type'],
                    'supplier_id' => $data['supplier_id'],
                ]
            );

            DB::commit();

            return responseOk($manufacture, "Se ha procesado correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error registrar la produccion.... ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $manufacture_id)
    {
        try {

            $manufacture = $store->manufactures()->with([
                'kardexes' => function ($k) {
                    $k->with(['variant.product', 'variant.optionValues']);
                },
                'purchases.supplier',
                'purchases.unit',
                'payments' => function ($p) {
                    $p->with(['gateway', 'images']);
                },
                'manufactureVariants.variant' => function ($q) {
                    $q->with([
                        'product',
                        'optionValues',
                    ]);
                },
            ])
                ->withSum('manufactureVariants as quantity_total', 'quantity')
                ->withSum('purchases as purchase_total', 'total')
                ->findOrFail($manufacture_id);

            return responseOk($manufacture);
        } catch (\Throwable $th) {

            Log::info($th);
            return responseError("Error al mostrar la manufactura");
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        //
    }

    public function search(Store $store, Request $request, $search)
    {
        //
        if (trim($search) === '') {
            return $this->index($store, $request);
        }

        try {
            // $products = $store->productDetails($warehouse)->get();

            $search = pluralToSingular($search);

            $result = $store->manufactures()
                ->with(['user'])
                ->withSum('manufactureVariants as sum_products', 'quantity')
                ->withSum('purchases as sum_purchases', 'total')
                ->search($search)->limit(10)->get();

            Log::info($result);

            return responseOk($result, "Datos obtenidos con exito de search");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Error al obtener los datos de search");
        }

        // $products = $store->products;

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, $manufacture_id)
    {
        //

        try {

            DB::beginTransaction();

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'budget' => 'nullable|numeric',
                'quantity_total' => 'nullable|integer',
            ]);

            $manufacture = $store->manufactures()->findOrFail($manufacture_id);
            $manufacture->update($data);

            DB::commit();

            return responseOk($manufacture, "Se ha actualizado correctamente la manufactura");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al actualizar la manufactura.... ");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
