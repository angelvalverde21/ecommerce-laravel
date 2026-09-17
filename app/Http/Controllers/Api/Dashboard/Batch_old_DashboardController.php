<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BatchItem;
use App\Models\Store;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //


        return responseOk(
            $store->batches()->with('items.variant.product', 'items.variant.optionValues')->get(),
            'Lista de batches'
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        //
    }
    /**
     * Show the form for creating a new resource.
     */
    public function batch(Store $store, Request $request)
    {
        try {

            DB::beginTransaction();

            //

            // Log::info($request);

            //Comprueba que los numeros del array sean enteros y existan en la tabla variants, el asterisco '*' indica que se validará cada elemento del array
            // [x,y,z...] => cada elemento del array se validará con la regla 'integer|exists:variants,id'

            $variants = $request->all();

            // Log::info($request->all());

            $variants = $request->validate([
                '*.id' => 'required|integer|exists:variants,id',
            ]);

            $firstVariant = Variant::with('product')
                ->findOrFail($variants[0]['id']);

            Log::info($firstVariant);

            //preg_replace('/\s+\S+$/', '', trim($variant->product->name)) Elimina la última palabra del nombre del producto para obtener el nombre base, por ejemplo "Polera Doble Capucha - Lote 20240407123045" se convierte en "Polera Doble Capucha"

            $name = preg_replace('/\s+\S+$/', '', trim($firstVariant->product->name)) . ' - Lote ' . now()->format('YmdHis'); // Ejemplo: "Polera Doble Capucha - Lote 20240407123045"

            $batch = $store->batches()->create([
                'name' => $name,
                'user_id' => Auth::id(),
                'store_id' => $store->id,
            ]);


            foreach ($variants as $variant) {
                /*
                BatchItem::updateOrCreate: Busca la variant_id en toda la tabla
                Si existe → la mueve de batch
                Si no → la crea
                Garantiza: una variante = un solo lote
            */

                $batchItem = $batch->items()->create([
                    'variant_id' => $variant['id'],
                ]);

                // $batchItem->load(['variant.product', 'variant.optionValues']); //
                $batchItems[] = $batchItem;
            }

            DB::commit();

            return responseOk(
                $batch->load('items.variant.product', 'items.variant.optionValues'),
                'Se agregaron correctamente los variants al manufacture'
            );
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al eliminar.... ");
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $batch_id)
    {
        //
        //
        try {
            $batch = $store->batches()->with('items.variant.product.image', 'items.variant.optionValues')->findOrFail($batch_id);

            return responseOk(
                $batch,
                'Batch encontrado'
            );
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Batch no encontrado");
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store) {}

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
