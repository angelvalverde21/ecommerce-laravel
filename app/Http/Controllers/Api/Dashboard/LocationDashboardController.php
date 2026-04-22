<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Store;
use Illuminate\Http\Request;

class LocationDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

        // Traer todas las locations del store
    public function index(Store $store)
    {
        $locations = $store->locations()
            ->whereNull('parent_id') // solo raíces
            ->with('children') // cargar hijos recursivamente
            ->orderBy('id') // opcional
            ->get();

        return responseOk($locations);
    }
    

    /**
     * Construye el árbol jerárquico
     */

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
