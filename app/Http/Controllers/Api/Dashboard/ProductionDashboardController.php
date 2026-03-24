<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Manufacture;
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


    protected ProductionService $productionService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->productionService = new ProductionService();
    }

    public function index(Store $store)
    {
        //
        try {

            $productions = $store->productions()
                ->with(['user'])
                ->withSum('productionVariants as sum_variants', 'quantity')
                // ->withSum('Productions as sum_Productions', 'total')
                ->get();

            return responseOk($productions, "Listado de producciones obtenido correctamente");
        } catch (\Throwable $th) {

            Log::info($th);
            return responseError("Error al obtener el listado de producciones");
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
        return $this->productionService->store($store, $request);
    }


    public function show(Store $store, $production_id)
    {
        try {

            $production = $store->productions()
                            ->with(['purchases.items'])
                            ->withSum('productionVariants as sum_variants', 'quantity')
                            ->withSum('kardexes as sum_kardexes', 'quantity')
                            ->findOrFail($production_id);


            // $total = $manufacture->Productions
            //     ->flatMap->items
            //     ->sum('subtotal');

            // $manufacture->Production_total = $total;

            $total = $production->purchases
                ->flatMap->items
                ->sum('subtotal');

            $production->sum_purchases = $total;

            return responseOk($production, 'Producción obtenida correctamente');

        } catch (\Throwable $th) {

            Log::error($th->getMessage());

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
