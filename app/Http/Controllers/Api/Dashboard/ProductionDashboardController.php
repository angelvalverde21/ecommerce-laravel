<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Manufacture;
use App\Models\Production;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Dashboard\Crud\ProductionService;

class ProductionDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    protected ProductionService $service;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->service = new ProductionService();
    }

    public function index(Store $store)
    {
        return responseOk($this->service->index($store), "El listado de producciones ha sido obtenido correctamente (dashboard)");
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
                ->withSum('Productions as sum_Productions', 'total')
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
    public function store(Store $store, Request $request)
    {
        //

        $production = $this->service->store($store, $request);

        Log::info($production);

        return responseOk($production, "Producción creada correctamente");
    }


    public function show(Store $store, $id)
    {
        $production = $this->service->show($store, $id);

        if (!$production) {
            return responseError("Error al obtener la producción");
        }

        return responseOk($production, 'Producción obtenida correctamente');
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
