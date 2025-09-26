<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BrandDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
        try {

            $brands = Brand::where("store_id", $store->id)->get();

            return responseOk($brands, "Se ha procesado correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            return responseError($th, "Error al obtener las marcas.... ");
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
    public function store(Store $store, Request $request)
    {
        //


        try {

            DB::beginTransaction();

            $data = $request->all();

            $brand = Brand::create(
                [
                    "name" => $data["name"],
                    'slug' => Str::slug($data["name"]),
                    "store_id" => $store->id,
                    "status" => 1
                
                ]
            );

            DB::commit();

            return responseOk($brand, "Se ha procesado correctamente");
        } catch (\Throwable $th) {

            DB::rollback();

            Log::info($th);

            return responseError($th, "Error al crear la marca.... ");
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
