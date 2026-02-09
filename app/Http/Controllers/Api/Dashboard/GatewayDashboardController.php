<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GatewayDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
        try {
        
            $gateways = $store->gateways; // o $store->gateways()->get();

            return responseOk($gateways, "Se ha procesado correctamente");

        } catch (\Throwable $th) {
        
            Log::info($th);
        
            return responseError("Error al eliminar.... ");
        
        }
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

            $validated = $request->validate([
                'title' => 'required|string',
            ]);

            $gateway = $store->gateways()->create(
                [
                    'name' => Str::slug($validated['title'], '_'),
                    'title' => $validated['title'],
                ]
            );

            DB::commit();

            return responseOk($gateway, "Se ha procesado correctamente el gateway");

        } catch (\Throwable $th) {
        
            Log::info($th);
        
            DB::rollback();

            return responseError("Error al crear el gateway");

        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $gateway_id)
    {
        //
        try {
        
            $gateway = $store->gateways()->findOrFail($gateway_id);

            return responseOk($gateway, "Se ha procesado correctamente");

        } catch (\Throwable $th) {
        
            Log::info($th);
        
            return responseError("Error al obtener el gateway");

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
    public function update(Request $request, Store $store, $gateway_id)
    {
        //
        try {
        
            DB::beginTransaction();

            $validated = $request->validate([
                'title' => 'required|string',
            ]);

            $gateway = $store->gateways()->findOrFail($gateway_id);

            $gateway->update([
                'name' => Str::slug($validated['title']),
                'title' => $validated['title'],
            ]);

            DB::commit();

            return responseOk($gateway, "Se ha procesado correctamente la actualización del gateway");

        } catch (\Throwable $th) {
        
            Log::info($th);
        
            DB::rollback();

            return responseError("Error al actualizar el gateway");

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
