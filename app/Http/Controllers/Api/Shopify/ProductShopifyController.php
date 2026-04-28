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

    public function index(Request $request)
    {
        try {

            //Leer query params
            $status  = $request->query('status', 'ACTIVE');
            $page    = max(1, (int) $request->query('page', 1));
            $perPage = (int) $request->query('per_page', 20);

            // Llamar al servicio con parámetros
            $products = $this->shopifyProductService->getProducts(
                $status,
                $perPage,
                $page
            );

            return response()->json($products);
        } catch (\Throwable $th) {

            Log::error($th);

            return responseError($th, "Error al listar los productos.... ");
        }
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

    public function search(Store $store, Request $request, $search = "")
    {

        $search = $request->input('search', '');

        if (trim($search) === '' || $search === null) {
            Log::info("se ejectutó la búsqueda sin término de búsqueda, se devolverá el listado completo de Suppliers para el store con id: {$store->id}");
            return $this->shopifyProductService->getProducts();
        }

        $validated = $request->validate([
            'search'     => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',

        ]);

        $search     = $validated['search'];
        $startDate  = $validated['start_date'] ?? null;
        $endDate    = $validated['end_date'] ?? null;

        $query = $this->shopifyProductService->getSearchProducts($search);


        // if ($startDate && $endDate) {
        //     $query->whereBetween('products.created_at', [
        //         $startDate . ' 00:00:00',
        //         $endDate   . ' 23:59:59'
        //     ]);
        // }

        return responseOk($query, "Se ha procesado correctamente el resultado de la búsqueda de productos con el término: {$search}");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function sync(Store $store)
    {
        //
        try {
            $array = $this->shopifyProductService->sync($store);

            return response()->json($array);
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);

            return responseError($th, "Error al listar los resultados de busqueda de productos.... ");
        }
    }

    public function updatePrices(Store $store, Request $request)
    {

        Log::info("ok");

        $products = $request->all();

        foreach ($products as $product) {

            if ($product['variants']) {

                foreach ($product['variants'] as $v) {

                    ShopifyVariant::where('id', $v['id'])->update([
                        'price_etiqueta'    => $v['price_etiqueta'],
                        'price_oferta'      => $v['price_oferta'],
                        'price_sale'        => $v['price_sale'],
                        'price_wholesaler'  => $v['price_wholesaler'],
                        'price_live'        => $v['price_live'],
                        'price_blackfriday' => $v['price_blackfriday'],
                        'price_feria'       => $v['price_feria'],
                        'updated_at'        => now(),
                    ]);
                }

            }

        }
    }

    public function updatePrices__back(Store $store, Request $request)
    {
        $variants = $request->all();

        foreach ($variants as $v) {

            ShopifyVariant::where('id', $v['id'])->update([
                'price_etiqueta'    => $v['price_etiqueta'],
                'price_oferta'      => $v['price_oferta'],
                'price_sale'        => $v['price_sale'],
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
            'price_etiqueta'    => $request->price_etiqueta,
            'price_oferta'      => $request->price_oferta,
            'price_sale'        => $request->price_sale,
            'price_wholesaler'  => $request->price_wholesaler,
            'price_live'        => $request->price_live,
            'price_blackfriday' => $request->price_blackfriday,
            'price_feria'       => $request->price_feria,
            'updated_at'        => now(),
        ];

        ShopifyVariant::where('id', $request->id)->update($data);

        return responseOk([], "Precio actualizado correctamente");
    }

    public function updateProductVariantPrices(Store $store, Request $request, $product_id)
    {

        Log::info($request->all());

        // Validación mínima
        $request->validate([
            'price_etiqueta'    => 'nullable|numeric|min:0',
            'price_oferta'      => 'nullable|numeric|min:0',
            'price_sale'        => 'nullable|numeric|min:0',
            'price_wholesaler'  => 'nullable|numeric|min:0',
            'price_live'        => 'nullable|numeric|min:0',
            'price_blackfriday' => 'nullable|numeric|min:0',
            'price_feria'       => 'nullable|numeric|min:0',
        ]);

        // Payload (solo campos presentes), evita campos que no esten en la request
        $data = collect($request->only([
            'price_etiqueta',
            'price_oferta',
            'price_sale',
            'price_wholesaler',
            'price_live',
            'price_blackfriday',
            'price_feria',
        ]))->filter(fn($v) => !is_null($v))
            ->map(fn($v) => (float) $v)
            ->toArray();

        $data['updated_at'] = now();

        //UNA SOLA QUERY
        ShopifyVariant::where('shopify_product_id', $product_id)
            ->update($data);

        $variants = ShopifyVariant::where('shopify_product_id', $product_id)->get();


        return responseOk($variants, "Precios de variantes actualizados correctamente");
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

    public function updateProductSyncStatus(Store $store, Request $request, string $product_id)
    {
        //
        $request->validate([
            'sync_status' => 'required|boolean',
        ]);

        $product = ShopifyProduct::findOrFail($product_id);

        $product->update([
            'sync_status' => $request->sync_status,
        ]);

        return responseOk($product, "Estado de sincronización del producto actualizado correctamente");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
