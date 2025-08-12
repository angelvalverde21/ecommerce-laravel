<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Store;
use Illuminate\Http\Request;
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
        try {
            Log::info('exito');
            //selectFields esta en el modelo batch
            return responseOk($store->batches()->with('section.childrens')->orderBy('id', 'desc')->get(), "El listado de batchs ha sido obtenido correctamente (dashboard)");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
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
    public function store(Store $store, Request $request) //create
    {

        $resp = $request->all();

        Log::info('creando batcho');

        // $rules = $this->rules;

        // $this->validate($rules);

        try {


            DB::beginTransaction();


            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'total' => 'nullable|numeric',
                'quantity_approved' => 'nullable|numeric',
                'quantity_waste' => 'nullable|numeric',
                'production_cost' => 'nullable|numeric',
                'store_id' => '',
                'section_id' => '',
            ]);

            $validatedData['section_id'] = 1;
            $validatedData['store_id'] = $store->id;

            $batch = Batch::create($validatedData);


            // return redirect()->route('erp.batchs.edit', ['store' => $this->store, 'batch' => $batch]);

            DB::commit();

            return responseOk($batch, "se agrego correctamente el batch en create");
        } catch (\Throwable $th) {

            DB::rollback();

            Log::info($th);

            return responseError($th, "Ha sucedido un error interno al crear el batcho store x");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $batch_id)
    {
        // $batch = $store->batches()->with(['section.childrens.purchases'])->find($batch_id);

        // $batch = $store->batches()
        //     ->with(['section.childrens.purchases' => function ($query) {
        //         $query->with(['unit', 'supplier']);
        //     }])
        //     ->find($batch_id);

        // Log::info($batch->toSql());

        $batch = $store->batches()
            ->with(['section.childrens' => function ($q) use ($batch_id) {
                $q->with(['purchases' => function ($query) use ($batch_id) {
                    $query->where('purchaseable_type', \App\Models\Batch::class)
                        ->where('purchaseable_id', $batch_id)
                        ->with(['unit', 'supplier']);
                }]);
            }])
            ->find($batch_id);

        if (!$batch) {
            return responseError([], "Error al obtener el batcho x");
        }

        return responseOk($batch, "Datos obtenidos con exito del batcho");
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Store $store, $batch_id, Request $request)
    {
        // Implementa la lógica para actualizar un batcho existente
        // Esto podría implicar validar los datos de la solicitud,
        // actualizar el batcho en la base de datos y devolver el batcho actualizado.
        try {
            Log::info('updatex');
            Log::info($request->all());

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'total' => 'nullable|numeric',
                'quantity_approved' => 'nullable|numeric',
                'quantity_waste' => 'nullable|numeric',
                'production_cost' => 'nullable|numeric',
            ]);

            $validatedData['store_id'] = $store->id;

            $batch = Batch::updateOrCreate(
                ['id' => $batch_id],  // El campo 'id' indica si se actualiza o crea

                $validatedData
            );

            return responseOk($batch, "Datos guardados correctamente update");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Error al guardar los datos del batcho desde batch Private controller - > update", $th);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
