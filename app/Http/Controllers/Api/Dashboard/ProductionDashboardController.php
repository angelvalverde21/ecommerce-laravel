<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductionDashboardController extends Controller
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
                ->where('type', 'production')
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
    public function create()
    {
        //
    }

    public function search(Store $store, Request $request, $search = '')
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
                ->where('type', 'production')
                ->search($search)->limit(10)->get();

            Log::info($result);

            return responseOk($result, "Datos obtenidos de las producciones con exito de search");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Error al obtener los datos de search de las producciones");
        }

        // $products = $store->products;

    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $production_id)
    {
        try {

            $manufacture = $store->manufactures()->with([
                'kardexes' => function ($k) {
                    $k->with(['variant.product.image', 'variant.optionValues']);
                },
                'user',
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
                ->findOrFail($production_id);

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
