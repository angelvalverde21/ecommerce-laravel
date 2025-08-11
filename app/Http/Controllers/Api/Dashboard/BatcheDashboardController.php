<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Batche;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatcheDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
        try {
            Log::info('exito');
            //selectFields esta en el modelo batche
            return responseOk($store->batches()->orderBy('id', 'desc')->get(), "El listado de batches ha sido obtenido correctamente (dashboard)");
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

        Log::info('creando batcheo');

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
            ]);

            $validatedData['store_id'] = $store->id;

            $batche = Batche::create($validatedData);


            // return redirect()->route('erp.batches.edit', ['store' => $this->store, 'batche' => $batche]);

            DB::commit();

            return responseOk($batche, "se agrego correctamente el batche en create");
        } catch (\Throwable $th) {

            DB::rollback();

            Log::info($th);

            return responseError($th, "Ha sucedido un error interno al crear el batcheo store x");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $batche_id)
    {
        $batche = $store->batches()->find($batche_id);

        if (!$batche) {
            return responseError([], "Error al obtener el batcheo x");
        }

        return responseOk($batche, "Datos obtenidos con exito del batcheo");
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
    public function update(Store $store, $batche_id, Request $request)
    {
        // Implementa la lógica para actualizar un batcheo existente
        // Esto podría implicar validar los datos de la solicitud,
        // actualizar el batcheo en la base de datos y devolver el batcheo actualizado.
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

            $batche = Batche::updateOrCreate(
                ['id' => $batche_id],  // El campo 'id' indica si se actualiza o crea

                $validatedData
            );

            return responseOk($batche, "Datos guardados correctamente update");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Error al guardar los datos del batcheo desde batche Private controller - > update", $th);
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
