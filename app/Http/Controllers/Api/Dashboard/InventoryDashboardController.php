<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
        return responseOk(
            $store->inventories()->with(
                [
                    'kardexes.variant.product',
                    'kardexes.variant.optionValues',
                ]
            )
                ->withSum('kardexes as sum_quantity', 'quantity')
                // ->orderByDesc('sum_quantity')
                ->get(),
            'Inventarios del store ' . $store->name
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
     * Store a newly created resource in storage.
     */
    public function store(Store $store, Request $request)
    {
        //
    }
    /**
     * Store a newly created resource in storage.
     */
    public function batch(Store $store, Request $request)
    {
        try {

            DB::beginTransaction();

            //

            Log::info($request);

            //Comprueba que los numeros del array sean enteros y existan en la tabla variants, el asterisco '*' indica que se validará cada elemento del array
            // [x,y,z...] => cada elemento del array se validará con la regla 'integer|exists:variants,id'

            $variants = $request->all();

            // Log::info($request->all());

            $variants = $request->validate([
                '*.variant_id' => 'required|integer|exists:variants,id',
                '*.quantity' => 'required|integer|min:1',
            ]);

            $firstVariant = Variant::with('product')
                ->findOrFail($variants[0]['variant_id']);

            Log::info($firstVariant);

            //preg_replace('/\s+\S+$/', '', trim($variant->product->name)) Elimina la última palabra del nombre del producto para obtener el nombre base, por ejemplo "Polera Doble Capucha - Lote 20240407123045" se convierte en "Polera Doble Capucha"

            $name = preg_replace('/\s+\S+$/', '', trim($firstVariant->product->name)) . ' - Lote ' . now()->format('YmdHis'); // Ejemplo: "Polera Doble Capucha - Lote 20240407123045"

            $inventory = $store->inventories()->create([
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

                // $inventoryItem[] = $inventory->items()->create([
                //     'variant_id' => $variant['variant_id'],
                // ]);

                $kardexes[] = $inventory->kardexes()->create([
                    'product_id' => $firstVariant->product->id,
                    'variant_id' => $variant['variant_id'],
                    'store_id'   => $store->id,
                    'quantity'   => $variant['quantity'], // Se inicia con 0 porque el kardex se actualizará cuando se agreguen o retiren productos del inventario
                    'comment'    => $name,
                    'direction'  => 'in',
                ]);

                // $batchItem->load(['variant.product', 'variant.optionValues']); //

            }

            DB::commit();

            return responseOk(
                $inventory->load('kardexes.variant.product', 'kardexes.variant.optionValues'),
                'Se agregaron correctamente los variants al inventario ' . $inventory->name
            );
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al agregar los kardexes.... ");
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Store $store, $inventory_id)
    {
        try {
            $batch = $store->inventories()->with('kardexes.variant.product.image', 'kardexes.variant.optionValues')->findOrFail($inventory_id);

            return responseOk(
                $batch,
                'Inventory encontrado'
            );
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Batch no encontrado");
        }
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
