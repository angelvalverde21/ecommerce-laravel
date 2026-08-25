<?php

namespace App\Http\Controllers\Api\Dashboard\Acquires;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcquireVariantDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function batch(Store $store, Request $request, int $acquire_id)
    {

        try {

            DB::beginTransaction();

            $validated = $request->validate([
                '*' => 'integer|exists:variants,id',
            ]);

            $acquire_variants = [];


            $acquire = $store->acquires()
                ->findOrFail($acquire_id);

            foreach ($validated as $item) {

                $acquire_variant = $acquire->variants()->updateOrCreate(
                    [
                        'variant_id' => $item,
                    ],
                    [
                        'quantity' => 0,
                    ]
                );

                $acquire_variant->load(['variant.product.image', 'variant.optionValues']); //
                $acquire_variants[] = $acquire_variant;
            }

            
            DB::commit();

            return responseOk(
                $acquire_variants,
                'Se agregaron correctamente los variants al acquire'

            );
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al registrar el lote.... ");
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
    public function update(Store $store, Request $request, int $acquire_id, int $acquire_variant_id)
    {
        //

        try {

            DB::beginTransaction();

            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0',
                'price' => 'nullable|numeric|min:0',
            ]);

            $acquire = $store->acquires()
                ->findOrFail($acquire_id);

            $acquire_variant = $acquire->variants()
                ->with([
                    'variant.product.image',
                    'variant.optionValues'
                ])->findOrFail($acquire_variant_id);

            $acquire_variant->update([

                'quantity' => $validated['quantity'],
                'price' => $validated['price'],

            ]);

            DB::commit();

            return responseOk($acquire_variant, "Cantidad actualizada correctamente abc");
            
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al actualizar la cantidad.... ");
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, int $acquire_id, int $acquire_variant_id)
    {

        //

        try {

            DB::beginTransaction();

            $acquire = $store->acquires()
                ->findOrFail($acquire_id);

            $acquire_variant = $acquire->variants()
                ->findOrFail($acquire_variant_id); //busca en la tabla intermedia acquire_variant

            $acquire_variant->delete();

            DB::commit();

            return responseOk([], "Se ha eliminado correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al eliminar.... ");
        }
    }
}
