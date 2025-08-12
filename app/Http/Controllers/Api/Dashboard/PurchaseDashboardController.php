<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
            Log::info('exito index');
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

        $typeMap = [
            'batch' => \App\Models\Batch::class,
            // Agrega más modelos según tu caso
        ];


        $resp = $request->all();


        // $rules = $this->rules;

        // $this->validate($rules);


        $validated = $request->validate([
            'purchaseable_type' => ['required', Rule::in(array_keys($typeMap))],
            'purchaseable_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'unit_id' => 'required|integer|exists:units,id',
            'price' => 'required|numeric',
            'total' => 'required|numeric',
            'section_id' => 'required|integer|exists:sections,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'observations' => 'nullable|string',
        ]);


        $modelClass = $typeMap[$validated['purchaseable_type']];

        // Obtener nombre tabla para validar existencia
        $tableName = (new $modelClass)->getTable();

        $parentModel = $modelClass::where('store_id', $store->id)->findOrFail($validated['purchaseable_id']);

        try {


            DB::beginTransaction();

            $purchase = $parentModel->purchases()->create([
                'name' => $validated['name'],
                'quantity' => $validated['quantity'],
                'unit_id' => $validated['unit_id'],
                'price' => $validated['price'],
                'total' => $validated['total'],
                'section_id' => $validated['section_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'observations' => $validated['observations'] ?? null,
                'user_id' => Auth::guard('api')->id(),
                // No agregues store_id ni section_id aquí si es polimórfico
            ]);


            // return redirect()->route('erp.purchases.edit', ['store' => $this->store, 'purchase' => $purchase]);

            DB::commit();

            return responseOk($purchase->load('supplier'), "se agrego correctamente el purchase en create");

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


        $purchase = Purchase::find($purchase_id);

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
                'section_id' => 'required',
                'user_id' => '',
                'observations' => ''
            ]);

            // $validatedData['store_id'] = $store->id;
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
