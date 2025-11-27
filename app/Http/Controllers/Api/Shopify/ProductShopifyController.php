<?php

namespace App\Http\Controllers\Api\Shopify;


use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Support\Facades\Log;

class ProductShopifyController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $shopifyProductService;

    public function __construct(
        ShopifyProductService $shopifyProductService
    ) {
        $this->shopifyProductService = $shopifyProductService;
    }

    public function index()
    {
        try {

            $array = $this->shopifyProductService->getProducts(20); // traer 5 productos

            return response()->json($array);

            // return responseOk($products, "Se ha procesado correctamente el listado de productos de shopify");

        } catch (\Throwable $th) {

            Log::info($th);

            return responseError($th, "Error al listar los productos.... ");
        }


        // return response()->json($products);
    }

    public function search(Store $store, $search = "")
    {

        if (trim($search) === '') {
            return $this->index();
        }

        try {
            $array = $this->shopifyProductService->getProducts(20, $search);

            return response()->json($array);
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);

            return responseError($th, "Error al listar los resultados de busqueda de productos.... ");
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function sync(Store $store)
    {
        //
        try {
            $array = $this->shopifyProductService->sync();

            return response()->json($array);
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);

            return responseError($th, "Error al listar los resultados de busqueda de productos.... ");
        }
    }

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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
