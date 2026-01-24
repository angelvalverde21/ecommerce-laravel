<?php

namespace App\Http\Controllers\Api\Shopify\App;

use App\Http\Controllers\Controller;
use App\Models\store;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppShopifyController extends Controller
{


    protected $shopifyOrderService;

    public function __construct(ShopifyOrderService $shopifyOrderService)
    {
        $this->shopifyOrderService = $shopifyOrderService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function tracking(Request $request)
    {
        //
        try {

            DB::beginTransaction();

            $order = $this->shopifyOrderService->orderByNumber($request->order_id);
            Log::info("xxxxxxxxxxxxxxxxxxxxxxxx");
            Log::info($order);
            Log::info($order['shippingAddress']['phone']);

            if ($order['shippingAddress']['phone'] == $request->phone) {
                # code...
            }

            DB::commit();

            return responseOk($order, "Se ha procesado correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError($th, "Error al consultar.... ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(store $store)
    {
        //
    }
}
