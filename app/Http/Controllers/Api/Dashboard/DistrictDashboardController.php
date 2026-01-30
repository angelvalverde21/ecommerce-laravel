<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Dashboard\District\DistrictService;

class DistrictDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected DistrictService $districtService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->districtService = new DistrictService();
    }

    public function index()
    {
        //
    }
    public function search(Store $store, $search)
    {
        //
        try {

            return responseOk($this->districtService->search($search, 25), "Datos obtenidos con exito de search");

        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Error al obtener los datos de search");
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
    public function show(Store $store, $district_id)
    {
        //
        try {

            return responseOk($this->districtService->show($district_id), "Datos obtenidos con exito de show");

        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Error al obtener los datos de show");
        }
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
