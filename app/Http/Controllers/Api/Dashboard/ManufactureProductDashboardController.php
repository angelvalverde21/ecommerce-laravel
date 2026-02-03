<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\store;
use Illuminate\Http\Request;

class ManufactureProductDashboardController extends Controller
{
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(store $store)
    {
        //
    }

    //Procesa el lote que recibira por el request
    public function batch(Request $request, store $store, $manufacture_id)
    {

        $validaded = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.manufacture_id' => 'required|integer|exists:manufactures,id',
            'items.*.variant_id' => 'required|integer|exists:variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        foreach ($request->items as $item) {
            $store->manufactures()->find($manufacture_id)->products()->attach(
                
            );
        }
        
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
