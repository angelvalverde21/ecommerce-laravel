<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\Crud\ProductionPurchaseService;
use Illuminate\Http\Request;

class ProductionPurchaseDashboardController extends Controller
{

    protected ProductionPurchaseService $productionPurchaseService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->productionPurchaseService = new ProductionPurchaseService();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $production_id)
    {
        //
        return responseOk($this->productionPurchaseService->index($store, $production_id));
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
