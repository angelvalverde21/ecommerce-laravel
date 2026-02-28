<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderShopifyController extends Controller
{

    protected $shopifyOrderService;

    public function __construct(ShopifyOrderService $shopifyOrderService)
    {
        $this->shopifyOrderService = $shopifyOrderService;
    }

    public function index(Store $store, Request $request)
    {

        // $orders = Cache::remember('shopify:orders:20', now()->addMinutes(60), function () {
        //     return $this->shopifyOrderService->getOrders(20);

        // });

        Log::info($request);

        $orders = $this->shopifyOrderService->getOrders(20, $request->cursor);

        return response()->json($orders);
    }

    public function search(Store $store, Request $request, $search)
    {

        // $orders = Cache::remember('shopify:orders:20', now()->addMinutes(60), function () {
        //     return $this->shopifyOrderService->getOrders(20);

        // });

        Log::info($request);

        $orders = $this->shopifyOrderService->getSearchOrders(20, $request->cursor, $search);

        return response()->json($orders);
    }

    public function pending(Store $store, Request $request)
    {

        // $orders  = Cache::remember(
        //     "orders_cache__?_xxxp___",
        //     now()->addHour(1),
        //     fn() => $this->shopifyOrderService->getOrders(10)
        // );

        // $orders = Cache::remember('shopify:orders:pendixng:2x0s', now()->addMinutes(60), function () {
        //     return $this->shopifyOrderService->getOrdersPending(20);
        // });


        $orders = $this->shopifyOrderService->getOrdersPending(20, $request->cursor);

        // $orders = $this->shopify->getOrders(20); // Trae 20 órdenes
        return response()->json($orders);
    }

    public function prepared_aylin(Store $store, Request $request)
    {

        // $orders  = Cache::remember(
        //     "orders_cache__?_xxxp___",
        //     now()->addHour(1),
        //     fn() => $this->shopifyOrderService->getOrders(10)
        // );

        // $orders = Cache::remember('shopify:orders:prepared:10', now()->addMinutes(60), function () {
        //     return $this->shopifyOrderService->getOrdersPrepared(10); //ultimos 10 dias
        // });

        $orders = $this->shopifyOrderService->getOrdersAylin(150, $request->cursor); //ultimos 10 dias

        // $orders = $this->shopify->getOrders(20); // Trae 20 órdenes
        return response()->json($orders);
    }

    public function prepared(Store $store, Request $request)
    {

        // $orders  = Cache::remember(
        //     "orders_cache__?_xxxp___",
        //     now()->addHour(1),
        //     fn() => $this->shopifyOrderService->getOrders(10)
        // );

        // $orders = Cache::remember('shopify:orders:prepared:10', now()->addMinutes(60), function () {
        //     return $this->shopifyOrderService->getOrdersPrepared(10); //ultimos 10 dias
        // });

        $orders = $this->shopifyOrderService->getOrdersYen(150, $request->cursor); //ultimos 10 dias

        // $orders = $this->shopify->getOrders(20); // Trae 20 órdenes
        return response()->json($orders);
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
    public function store(Store $store, Request $request)
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
