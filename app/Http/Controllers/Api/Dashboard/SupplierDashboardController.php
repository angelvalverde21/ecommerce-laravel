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
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
