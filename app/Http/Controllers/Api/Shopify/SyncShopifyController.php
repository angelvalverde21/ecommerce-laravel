<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncShopifyController extends Controller
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

    public function synPrice(Store $store, Request $request)
    {

        // Log::info("Sync");
        // Log::info($request);

        try {
            return $this->shopifyProductService->syncPrice($request);
        } catch (\Throwable $th) {
            //throw $th;
        }
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
