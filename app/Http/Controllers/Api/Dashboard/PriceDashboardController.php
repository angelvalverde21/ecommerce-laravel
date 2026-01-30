<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PriceDashboardController extends Controller
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

        // Log::info('creando attribute0');

        // $rules = $this->rules;

        // $this->validate($rules);

        try {
        
            DB::beginTransaction();

            $product = Product::findOrFail($product_id);


            $price = $product->prices()->create(
                [
                    'name' => $resp['name'],
                    'value' => $resp['value'],
                ]
            );

            DB::commit();
        
            return responseOk($price, "Se ha creado correctamente el precio");
        
        } catch (\Throwable $th) {
        

            Log::info($th);

            DB::rollback();
        
            return responseError("Error al crear el precio.... ");
        
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
    public function destroy(Store $store)
    {
        //
    }
}
