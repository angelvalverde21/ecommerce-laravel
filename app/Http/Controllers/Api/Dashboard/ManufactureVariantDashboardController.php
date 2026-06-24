<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufactureVariantDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, int $manufacture_id)
    {
        //

        $manufacture_variants = $store->manufactures()
            ->findOrFail($manufacture_id)
            ->manufactureVariants()
            ->with([
                'variant.product.image',
                'variant.optionValues',
                'variant.manufactureKardexes' => fn($q) =>
                $q->where('kardexable_id', $manufacture_id)
            ])
            ->get();

        return responseOk($manufacture_variants, "Listado de Manufacture_variants obtenido correctamente");
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

    public function batch(Store $store, Request $request, $manufacture_id)
    {

        try {

            DB::beginTransaction();

            $validated = $request->validate([
                '*' => 'integer|exists:variants,id',
            ]);

            $manufacture_variants = [];


            $manufacture = $store->manufactures()
                ->findOrFail($manufacture_id);

            foreach ($validated as $item) {

                $manufacture_variant = $manufacture->manufactureVariants()->updateOrCreate(
                    [
                        'variant_id' => $item,
                    ],
                    [
                        'quantity' => 0,
                    ]
                );

                $manufacture_variant->load(['variant.product', 'variant.optionValues']); //
                $manufacture_variants[] = $manufacture_variant;
            }

            DB::commit();

            return responseOk(
                $manufacture_variants,
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
    // public function update(Request $request, store $store)
    // {
    //     //
    // }

    public function updateQuantity(Store $store, Request $request, $manufacture_id, $manufacture_variant_id)
    {
        //


        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0',
            ]);

            $manufacture = $store->manufactures()
                ->findOrFail($manufacture_id);

            $manufacture_variant = $manufacture->manufactureVariants()->with([
                'variant.manufactureKardexes' => fn($q) =>
                $q->where('kardexable_id', $manufacture_id)
            ]);

            $manufacture_variant->update([

                'quantity' => $validated['quantity'],

            ]);

            DB::commit();

            return responseOk($manufacture_variant->load(['variant.product', 'variant.optionValues']), "Cantidad actualizada correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al actualizar la cantidad.... ");
        }
    }

    public function update(Store $store, Request $request, int $manufacture_id, int $manufacture_variant_id)
    {
        //

        try {

            DB::beginTransaction();
            
            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0',
                'price' => 'nullable|numeric|min:0',
            ]);

            $manufacture = $store->manufactures()
                ->findOrFail($manufacture_id);

            $manufacture_variant = $manufacture->manufactureVariants()
                                                    ->with([
                                                        'variant.product.image',
                                                        'variant.optionValues'
                                                    ])->findOrFail($manufacture_variant_id);

            $manufacture_variant->update([

                'quantity' => $validated['quantity'],
                'price' => $validated['price'],

            ]);

            DB::commit();

            return responseOk($manufacture_variant , "Cantidad actualizada correctamente abc");

        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al actualizar la cantidad.... ");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, $manufacture_id, $manufacture_variant_id)
    {

        //

        try {

            DB::beginTransaction();

            $manufacture = $store->manufactures()
                ->findOrFail($manufacture_id);

            $manufacture_variant = $manufacture->manufactureVariants()
                ->findOrFail($manufacture_variant_id);

            $manufacture_variant->delete();

            DB::commit();

            return responseOk([], "Se ha eliminado correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al eliminar.... ");
        }
    }
}
