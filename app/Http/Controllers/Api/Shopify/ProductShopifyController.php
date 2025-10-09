<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ShopifyService;

class ProductShopifyController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(ShopifyService $shopify)
    {
        $products = $shopify->getProducts(25); // traer 5 productos
        return response()->json($products);
    }

    public function search($search, ShopifyService $shopify)
    {
        $limit = 10;
        // $cursor = $request->input('cursor', null);
        // $searchTerm = $request->input('query', null);

        return $shopify->getProducts($limit, $search);
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
