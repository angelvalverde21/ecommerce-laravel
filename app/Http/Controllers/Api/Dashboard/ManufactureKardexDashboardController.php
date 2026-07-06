<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\ManufactureKardexService;
use Illuminate\Http\Request;

class ManufactureKardexDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $service;

    public function __construct(ManufactureKardexService $manufactureKardexService)
    {
        $this->service = $manufactureKardexService;
    }


    public function index(Store $store, int $manufacture_id)
    {
        //
        $kardexes = $this->service->index($store, $manufacture_id);

        return responseOk($kardexes, "Listado de Kardexes obtenido correctamente");
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
