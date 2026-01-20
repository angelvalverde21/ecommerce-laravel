<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\store;
use App\Models\Variant;
use App\Models\VariantOptionValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptionValueDashboardController extends Controller
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
    public function store(Store $store, Request $request) //create
    {

        $resp = $request->all();

        Log::info('creando OptionValue');

        // $rules = $this->rules;

        // $this->validate($rules);

        try {

            DB::beginTransaction();

            //Este create tiene un observer que esta atento se llama OptionValueObserver buscarlo con ctrl+p
            $optionValue = OptionValue::create([
                'option_id'  => $resp['option_id'],
                'value'      => $resp['value'],
                'sort_order' => 1,
            ]);

            DB::commit();

            return responseOk($optionValue->load('option'), "se agrego correctamente el optionValue en create con sus opciones");
            
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError($th, "Ha sucedido un error interno al crear el optionValue");
        }
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
