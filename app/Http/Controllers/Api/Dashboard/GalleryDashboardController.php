<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GalleryDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //

        try {
        
            DB::beginTransaction();
        
            //Aqui agregar el codigo
        
            DB::commit();
        
            return responseOk([], "Se ha procesado correctamente");
        
        } catch (\Throwable $th) {
        
            DB::rollback();
        
            return responseError("Error al eliminar.... ");
        
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
    public function show(store $store)
    {
        //
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
