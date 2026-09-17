<?php

namespace App\Http\Controllers\Api\Dashboard\Acquires;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\Acquire\AcquireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcquireDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    private $service;

    public function __construct(AcquireService $service)
    {
        $this->service = $service;
    }

    public function index(Store $store)
    {
        //
        return responseOk($this->service->index($store), "La informacion de la ordenes de compra han sido obtenida satisfactoriamente");

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
        try {
        
            DB::beginTransaction();
        
            $acquire = $this->service->store($store, $request);
        
            DB::commit();
        
            return responseOk($acquire, "Se ha creado correctamente la orden de compra");
        
        } catch (\Throwable $th) {
        
            Log::info($th);
        
            DB::rollback();
        
            return responseError("Error al crear la orden de compra");
        
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, int $acquire_id)
    {
        //
        return responseOk($this->service->show($store, $acquire_id), "Datos obtenidos con exito de la orden de compra");
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
    public function update(Store $store, Request $request, int $acquire_id)
    {
        //

        try {
        
            $acquire = $this->service->update($store, $request, $acquire_id);
        
            return responseOk($acquire, "Se ha actualizado correctamente la orden de compra");

        } catch (\Throwable $th) {

            Log::info($th);
            
            return responseError("Error al actualizar la orden de compra");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
