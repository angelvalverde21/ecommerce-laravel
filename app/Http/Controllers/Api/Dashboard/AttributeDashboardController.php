<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttributeDashboardController extends Controller
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
    public function store(Store $store, $product_id,  Request $request)
    {
        $resp = $request->all();

        Log::info('creando attribute0');

        // $rules = $this->rules;

        // $this->validate($rules);

        try {
        
            DB::beginTransaction();

            $attribute = Attribute::create(
                [
                    'store_id' => $store->id,
                    'product_id' => $product_id,
                    'name' => $resp['name'],
                    'value' => $resp['value'],
                    'sort_order' => 1,
                ]

            );
        
            DB::commit();
        
            return responseOk($attribute, "Se ha creado correctamente el atributo");
        
        } catch (\Throwable $th) {
        

            Log::info($th);

            DB::rollback();
        
            return responseError($th, "Error al crear el atributo.... ");
        
        }

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
    public function destroy(Store $store, $product_id, $attribute_id)
    {
        try {

            $product = Attribute::findOrFail($attribute_id);

            $product->delete();

            // $product->save();

            return responseOk([], "Atributo eliminado correctamente (destroy)");
        } catch (\Throwable $th) {

            Log::error($th);
            return responseError($th, "Error al eliminar el atributo (destroy)");
        }
    }

    
}
