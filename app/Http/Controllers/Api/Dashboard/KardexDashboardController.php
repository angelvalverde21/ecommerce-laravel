<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KardexDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected function getParentModel(array $validated): Model
    {
        $map = [
            'manufacture' => \App\Models\Manufacture::class,
            'production' => \App\Models\Production::class,
            // 'order' => \App\Models\Order::class,
            // 'purchase' => \App\Models\Purchase::class,
        ];

        if (! isset($map[$validated['kardexable_type']])) { //ojo kardexable_type viene del request (angular), no es un campo de la tabla kardexes
            throw ValidationException::withMessages([
                'kardexable_type' => 'Tipo de modelo no válido',
            ]);
        }

        return $map[$validated['kardexable_type']]::findOrFail($validated['kardexable_id']);
    }

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
    public function store(Store $store, Request $request)
    {
        //
    }

    public function batch(Store $store, Request $request)
    {
        //

        try {

            DB::beginTransaction();

            Log::info($request->all());

            $request->validate([
                '*.kardexable_type' => [
                    'required',
                    Rule::in(['manufacture', 'production'])
                ],

                '*.kardexable_id' => [
                    'required',
                    'integer'
                ],
                '*.variant_id'      => ['required', 'integer', 'exists:variants,id'],
                '*.quantity'        => ['required', 'numeric', 'min:1'],
                '*.comment'         => ['nullable', 'string', 'max:500'],
                '*.direction'       => ['required', 'in:in,out'],
                '*.kardexable_type' => ['required', 'string', 'in:manufacture,production'],
                '*.kardexable_id'   => ['required', 'integer'],
            ]);

            foreach ($request->all() as $data) {

                $parentModel = $this->getParentModel($data);

                Log::info($parentModel);

                $product = Variant::findOrFail($data['variant_id'])->product;

                $kardex = $parentModel->kardexes()->create([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'variant_id' => $data['variant_id'],
                    'quantity'   => $data['quantity'],
                    'comment'    => $data['comment'],
                    'direction'  => $data['direction'],
                ]);

                $kardexes[] = $kardex->load(['variant.product.image', 'variant.optionValues']);
            }

            DB::commit();

            return responseOk($kardexes, "Se ha procesado correctamente el kardex EN LOTE");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al eliminar.... ");
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

    public function getVariants(Store $store, $variant_id)
    {
        $kardexes = $store->kardexes()->where('variant_id', $variant_id)->with(['variant.product.image', 'variant.optionValues'])->get();

        return responseOk($kardexes, "Kardexes encontrados para el variant_id: $variant_id");
    }
}
