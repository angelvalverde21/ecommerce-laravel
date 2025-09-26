<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Models\Store;
use Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttributeValueDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $product_id)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store, $product_id)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, $product_id, Request $request)
    {
        //

        try {
        
            DB::beginTransaction();
        
            //Aqui agregar el codigo

            $attributeValue = AttributeValue::create($request->all());
        
            DB::commit();
        
            return responseOk($attributeValue, "Se ha procesado correctamente");
        
        } catch (\Throwable $th) {

            Log::info($th);
        
            DB::rollback();
        
            return responseError($th, "Error al eliminar.... ");
        
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $product_id, string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, $product_id, string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Store $store, $product_id, Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, $product_id, string $id)
    {
        //
    }
}
