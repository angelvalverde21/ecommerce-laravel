<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class InventoryDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
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
     * Store a newly created resource in storage.
     */
    public function batch(Store $store, Request $request)
    {
        //
        // $validated = $request->validate([
        //     '*' => 'integer|exists:variants,id',
        // ]);

        // $inventoryVariants = [];

        // $inventory = $store->inventorys()
        //     ->findOrFail($inventory_id);

        // foreach ($validated as $item) {

        //     $inventoryVariant = $inventory->inventoryVariants()->updateOrCreate(
        //         [
        //             'variant_id' => $item,
        //         ],
        //         [
        //             'quantity' => 0,
        //         ]
        //     );

        //     $inventoryVariant->load(['variant.product', 'variant.optionValues']); //
        //     $inventoryVariants[] = $inventoryVariant;
        // }

        // return responseOk(
        //     $inventoryVariants,
        //     'Se agregaron correctamente los variants al inventory'

        // );
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
