<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderManufactureDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        try {

            $manufactures = $store->manufactures()
                ->with(['user'])
                ->withSum('manufactureVariants as sum_products', 'quantity')
                ->withSum('purchases as sum_purchases', 'total')
                ->where('type', 'order')
                ->get();

            return responseOk($manufactures, "Listado de ordenes de manufactura obtenido correctamente");
        } catch (\Throwable $th) {

            // Log::info($th);
            return responseError("Error al obtener el listado de ordenes de manufactura");
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
                ->where('type', 'order')
                ->search($search)->limit(10)->get();

            Log::info($result);

            return responseOk($result, "Datos obtenidos de las ordenes de manufactura con exito de search");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Error al obtener los datos de search de las ordenes de manufactura");
        }

        // $products = $store->products;

    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $production_id)
    {
        try {

            $manufacture = $store->manufactures()->with([
                'supplier.user',
                'kardexes' => function ($k) {
                    $k->with(['variant.product.image', 'variant.optionValues']);
                },
                'purchases.supplier',
                'purchases.unit',
                'user',
                'payments' => function ($p) {
                    $p->with(['gateway', 'images']);
                },
                'manufactureVariants.variant' => function ($q) {
                    $q->with([
                        'product.image',
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
