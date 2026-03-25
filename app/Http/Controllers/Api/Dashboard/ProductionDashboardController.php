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
                ->with(['user', 'purchases.items'])
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


        $production = $this->productionService->store($store, $request);

        Log::info($production);

        return responseOk($production, "Producción creada correctamente");
    }


    public function show(Store $store, $production_id)
    {
        try {


            $production = $store->productions()
                ->select('*')
                ->selectSub(function ($query) {
                    $query->from('purchase_items')
                        ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                        ->whereColumn('purchases.purchaseable_id', 'productions.id')
                        ->where('purchases.purchaseable_type', \App\Models\Production::class)
                        ->selectRaw('SUM(purchase_items.subtotal)');
                }, 'sum_purchases')
                ->withSum('productionVariants as sum_variants', 'quantity')
                ->selectSub(function ($query) {
                    $query->from('kardexes')
                        ->whereColumn('kardexes.kardexable_id', 'productions.id')
                        ->where('kardexes.kardexable_type', Production::class)
                        ->selectRaw("
                            SUM(
                                CASE 
                                    WHEN direction = 'in' THEN quantity
                                    WHEN direction = 'out' THEN -quantity
                                    ELSE 0
                                END
                            )
                        ");
                }, 'sum_kardexes')
                ->findOrFail($production_id);

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
