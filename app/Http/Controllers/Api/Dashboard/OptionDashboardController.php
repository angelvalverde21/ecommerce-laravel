<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptionDashboardController extends Controller
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

        Log::info('creando option0');

        // $rules = $this->rules;

        // $this->validate($rules);

        $defaultOptionsByName = collect(OPTION::DEFAULT_OPTIONS)->keyBy('name');


        try {

            DB::beginTransaction();

            $option = Option::create(
                [
                    'store_id' => $store->id,
                    'product_id' => $product_id,
                    'name' => $resp['name'],
                    'label'      => $defaultOptionsByName[$resp['name']]['label'] ?? null,
                    'sort_order' => $defaultOptionsByName[$resp['name']]['sort_order'] ?? 1,
                ]
            );

            DB::commit();

            return responseOk($option, "Se ha creado correctamente el atributo");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError($th, "Error al crear el atributo.... ");
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
