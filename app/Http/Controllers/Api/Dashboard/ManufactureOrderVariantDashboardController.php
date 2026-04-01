<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class ManufactureOrderVariantDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $manufacture_id)
    {
        $manufactureVariants = $store->manufactures()
            ->findOrFail($manufacture_id)
            ->manufactureVariants()
            ->with(['variant.product.image', 'variant.optionValues'])
            ->get();

        return responseOk($manufactureVariants, "Listado de ManufactureOrderVariants obtenido correctamente");
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
