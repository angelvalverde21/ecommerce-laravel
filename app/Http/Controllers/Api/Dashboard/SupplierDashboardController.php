<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

class SupplierDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        // Implementa la lógica para recuperar y devolver productos para el panel de control
        // Esto podría implicar consultar la base de datos por productos relacionados con la tienda
        // y devolverlos en un formato paginado o como una colección.

        try {
            Log::info('exito');
            //selectFields esta en el modelo Product
            return responseOk($store->suppliers, "El listado de suppliers dashboard ha sido obtenido correctamente (dashboard)");
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

        Log::info('creando suppliero');

        // $rules = $this->rules;

        // $this->validate($rules);

        try {


            DB::beginTransaction();


            $supplier = Supplier::create(
                [
                    'name' => $resp['name'],
                    'email' => $resp['email'],
                    'address' => $resp['address'],
                    'identity_id' => $resp['identity_id'] != null ? $resp['identity_id'] : null,
                    'document_number' => $resp['document_number'] != null ?  $resp['document_number'] : null,
                    'phone' => $resp['phone'],
                    'store_id' => $store->id,
                ]
            );


            // return redirect()->route('erp.suppliers.edit', ['store' => $this->store, 'supplier' => $supplier]);

            DB::commit();

            return responseOk($supplier, "se agrego correctamente el supplier en create");
        } catch (\Throwable $th) {

            DB::rollback();

            Log::info($th);

            return responseError($th, "Ha sucedido un error interno al crear el suppliero store x");
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Store $store, $supplier_id)
    {

        $supplier = $store->suppliers()->find($supplier_id);

        if (!$supplier) {
            return responseError([], "Error al obtener el suppliero x");
        }

        return responseOk($supplier, "Datos obtenidos con exito del suppliero");
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
    public function update(Store $store, Request $request, string $supplier_id)
    {
        //
        // Implementa la lógica para actualizar un suppliero existente
        // Esto podría implicar validar los datos de la solicitud,
        // actualizar el suppliero en la base de datos y devolver el suppliero actualizado.
        try {
            Log::info('updatex');
            Log::info($request->all());

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable',
                'address' => 'nullable',
                'identity_id' => 'nullable',
                'document_number' => 'nullable',
                'phone' => 'nullable',
            ]);

            $validatedData['store_id'] = $store->id;

            $supplier = Supplier::updateOrCreate(
                ['id' => $supplier_id],  // El campo 'id' indica si se actualiza o crea

                $validatedData
            );
            return responseOk($supplier, "Datos guardados correctamente update");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Error al guardar los datos del suppliero desde supplier Private controller - > update", $th);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function search(Store $store, $search)
    {
        //
        try {
            Log::info('exito');
            //selectFields esta en el modelo Product
            return responseOk($store->suppliers()->search($search)->get(), "Elx listado de busqueda suppliers dashboard ha sido obtenido correctamente (dashboard)");
        } catch (\Throwable $th) {
            //throw $th;//
            Log::info($th);
        }
    }
}
