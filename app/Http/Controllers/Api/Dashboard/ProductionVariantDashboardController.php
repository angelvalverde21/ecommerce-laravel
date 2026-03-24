<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionVariantDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $production_id)
    {
        //

        $productionVariants = $store->productions()
            ->findOrFail($production_id)
            ->productionVariants()
            ->with(['variant.product.image', 'variant.optionValues'])
            ->get();

        return responseOk($productionVariants, "Listado de ManufactureVariants obtenido correctamente");
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

    public function batch(Store $store, Request $request, $production_id)
    {

        try {

            DB::beginTransaction();

            $validated = $request->validate([
                '*' => 'integer|exists:variants,id',
            ]);

            $productionVariants = [];


            $production = $store->productions()
                ->findOrFail($production_id);

            foreach ($validated as $item) {

                $productionVariant = $production->productionVariants()->updateOrCreate(
                    [
                        'variant_id' => $item,
                    ],
                    [
                        'quantity' => 0,
                    ]
                );

                $productionVariant->load(['variant.product', 'variant.optionValues']); //
                $productionVariants[] = $productionVariant;
            }

            DB::commit();

            return responseOk(
                $productionVariants,
                'Se agregaron correctamente los variants al manufacture'

            );
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al registrar el lote.... ");
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
    // public function update(Request $request, store $store)
    // {
    //     //
    // }

    public function updateQuantity(store $store, Request $request, $production_id, $production_variant_id)
    {
        //


        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0',
            ]);

            $production = $store->productions()
                ->findOrFail($production_id);

            $productionVariant = $production->productionVariants()
                ->findOrFail($production_variant_id);

            $productionVariant->update([

                'quantity' => $validated['quantity'],

            ]);

            DB::commit();

            return responseOk($productionVariant->load(['variant.product', 'variant.optionValues']), "Cantidad actualizada correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al actualizar la cantidad.... ");
        }
    }

    public function update(store $store, Request $request, $production_id, $production_variant_id)
    {
        //

        try {

            DB::beginTransaction();
            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0',
                'price' => 'nullable|numeric|min:0',
            ]);

            $production = $store->productions()
                ->findOrFail($production_id);

            $productionVariant = $production->productionVariants()
                ->findOrFail($production_variant_id);

            $productionVariant->update([

                'quantity' => $validated['quantity'],
                'price' => $validated['price'],

            ]);

            DB::commit();

            return responseOk($productionVariant->load(['variant.product.image', 'variant.optionValues']), "Cantidad actualizada correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al actualizar la cantidad.... ");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, $production_id, $production_variant_id)
    {

        //

        try {

            DB::beginTransaction();

            $production = $store->productions()
                ->findOrFail($production_id);

            $productionVariant = $production->productionVariants()
                ->findOrFail($production_variant_id);

            $productionVariant->delete();

            DB::commit();

            return responseOk([], "Se ha eliminado correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al eliminar.... ");
        }
    }
}
