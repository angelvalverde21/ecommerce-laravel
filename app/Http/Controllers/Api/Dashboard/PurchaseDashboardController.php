<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

class PurchaseDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
        try {
            Log::info('exito');
            //selectFields esta en el modelo purchase
            return responseOk($store->purchases()->with(['unit', 'supplier'])->orderBy('id', 'desc')->get(), "El listado de compras ha sido obtenido correctamente (dashboard)");
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
    public function store(Store $store, Request $request)
    {
        //
        $resp = $request->all();

        // $rules = $this->rules;

        // $this->validate($rules);

        try {


            DB::beginTransaction();


            $purchase = Purchase::create(
                [
                    'name' => $resp['name'],
                    'quantity' => $resp['quantity'],
                    'unit_id' => $resp['unit_id'],
                    'price' => $resp['price'],
                    'total' => $resp['total'],
                    'supplier_id' => $resp['supplier_id'],
                    'observations' => $resp['observations'],
                    'store_id' => $store->id,
                    'user_id' => Auth::guard('api')->id(),
                ]
            );


            // return redirect()->route('erp.purchases.edit', ['store' => $this->store, 'purchase' => $purchase]);

            DB::commit();

            return responseOk($purchase, "se agrego correctamente el purchase en create");
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError($th, "Ha sucedido un error interno al crear el purchaseo store x");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $purchase_id)
    {

        $purchase = $store->purchases()->find($purchase_id);

        if (!$purchase) {
            return responseError([], "Error al obtener el purchaseo x");
        }

        return responseOk($purchase, "Datos obtenidos con exito del purchaseo");
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
    public function update(Store $store, $purchase_id, Request $request)
    {
        // Implementa la lógica para actualizar un purchaseo existente
        // Esto podría implicar validar los datos de la solicitud,
        // actualizar el purchaseo en la base de datos y devolver el purchaseo actualizado.
        try {
            Log::info('updatex');
            Log::info($request->all());

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'quantity' => 'nullable',
                'unit_id' => 'nullable',
                'price' => 'nullable',
                'total' => 'nullable',
                'supplier_id' => 'nullable',
                'store_id' => '',
                'user_id' => '',
                'observations' => ''
            ]);

            $validatedData['store_id'] = $store->id;
            $validatedData['user_id']  = Auth::guard('api')->id();

            $purchase = Purchase::updateOrCreate(
                ['id' => $purchase_id],  // El campo 'id' indica si se actualiza o crea

                $validatedData
            );
            return responseOk($purchase, "Datos guardados correctamente update");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Error al guardar los datos del purchaseo desde purchase Private controller - > update", $th);
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
