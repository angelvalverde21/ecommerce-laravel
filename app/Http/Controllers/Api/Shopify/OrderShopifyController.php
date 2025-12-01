<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrderShopifyController extends Controller
{

    protected $shopifyOrderService;

    public function __construct(ShopifyOrderService $shopifyOrderService)
    {
        $this->shopifyOrderService = $shopifyOrderService;
    }

    public function index()
    {

        // $orders  = Cache::remember(
        //     "orders_cache__?_xxxp___",
        //     now()->addHour(1),
        //     fn() => $this->shopifyOrderService->getOrders(10)
        // );

        $orders = $this->shopifyOrderService->getOrders(200);

        // $orders = $this->shopify->getOrders(20); // Trae 20 órdenes
        return response()->json($orders);
    }

    public function pending()
    {

        // $orders  = Cache::remember(
        //     "orders_cache__?_xxxp___",
        //     now()->addHour(1),
        //     fn() => $this->shopifyOrderService->getOrders(10)
        // );

        $orders = $this->shopifyOrderService->getOrdersPending(200);

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
