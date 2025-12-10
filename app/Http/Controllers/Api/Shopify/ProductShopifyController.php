<?php

namespace App\Http\Controllers\Api\Shopify;


use App\Http\Controllers\Controller;
use App\Models\ShopifyProduct;
use App\Models\ShopifyVariant;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Support\Facades\DB;
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

            $products = $this->shopifyProductService->getProducts();

            return responseOk($products, "Se ha procesado correctamente el listado de productos de shopify");

        } catch (\Throwable $th) {

            Log::info($th);

            return responseError($th, "Error al listar los productos.... ");
        }

        // return response()->json($products);
    }
    public function active()
    {
        try {

            $products = $this->shopifyProductService->getProducts();

            return responseOk($products, "Se ha procesado correctamente el listado de productos de shopify");

        } catch (\Throwable $th) {

            Log::info($th);

            return responseError($th, "Error al listar los productos.... ");
        }

        // return response()->json($products);
    }
    public function draft()
    {
        try {

            $products = $this->shopifyProductService->getProducts('DRAFT');

            return responseOk($products, "Se ha procesado correctamente el listado de productos DRAFT");

        } catch (\Throwable $th) {

            Log::info($th);

            return responseError($th, "Error al listar los productos.... ");
        }

        // return response()->json($products);
    }
    public function archived()
    {
        try {

            $products = $this->shopifyProductService->getProducts('ARCHIVED');

            return responseOk($products, "Se ha procesado correctamente el listado de productos ARCHIVED");

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

            $products = $this->shopifyProductService->getSearchProducts($search);

            return responseOk($products, "Se ha procesado correctamente el listado de productos de shopify");

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

    public function updatePrices(Store $store, Request $request)
    {
        $variants = $request->all();

        foreach ($variants as $v) {
            ShopifyVariant::where('id', $v['id'])->update([
                'price_normal'      => $v['price_normal'],
                'price_wholesaler'  => $v['price_wholesaler'],
                'price_live'        => $v['price_live'],
                'price_blackfriday' => $v['price_blackfriday'],
                'price_feria'       => $v['price_feria'],
                'updated_at'        => now(),
            ]);
        }

        return responseOk([], "Precios actualizados correctamente");
    }

    public function updatePrice(Store $store, Request $request)
    {
        // Validación mínima (solo aseguramos que haya ID)
        $request->validate([
            'id' => 'required|integer'
        ]);

        // Armamos el payload de actualización
        $data = [
            'price_normal'      => $request->price_normal,
            'price_wholesaler'  => $request->price_wholesaler,
            'price_live'        => $request->price_live,
            'price_blackfriday' => $request->price_blackfriday,
            'price_feria'       => $request->price_feria,
            'updated_at'        => now(),
        ];

        ShopifyVariant::where('id', $request->id)->update($data);

        return responseOk([], "Precio actualizado correctamente");
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
