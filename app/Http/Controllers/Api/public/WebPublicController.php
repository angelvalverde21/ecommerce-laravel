<?php

namespace App\Http\Controllers\Api\public;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Http\Request;

class WebPublicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $shopifyOrderService;

    public function __construct(ShopifyOrderService $shopifyOrderService)
    {
        $this->shopifyOrderService = $shopifyOrderService;
    }

    public function tracking(Store $store, $order_id)
    {
        //
        $order = $this->shopifyOrderService->getOrderByName($order_id);

        return response()->json($order);
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
    public function show(Store $store)
    {
        //
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
