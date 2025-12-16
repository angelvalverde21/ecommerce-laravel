<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        try {
            Log::info('exito purchase_orders index');
            //selectFields esta en el modelo purchase
            return responseOk(
                $store->purchaseOrders()->with(['user', 'store', 'supplier'])
                        ->orderBy('id', 'desc')
                            ->get(), "El listado de ordenes de compra ha sido obtenido correctamente (dashboard)");
        } catch (\Throwable $th) {
            //throw $th;
            // Log::info($th);
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

        try {

            DB::beginTransaction();

            $data = $request->validate([
                'name'          => ['required', 'string', 'max:255'],
                'observations'  => ['nullable', 'string'],
                'supplier_id'   => ['required', 'exists:suppliers,id'],
            ]);

            $purchaseOrder = PurchaseOrder::create([
                'name'         => $data['name'],
                'observations' => $data['observations'] ?? null,
                'supplier_id'  => $data['supplier_id'],
                'store_id'     => $store->id ?? null,
                'user_id'      => Auth::id(), // usuario que crea la orden
            ]);

            DB::commit();

            return responseOk($purchaseOrder, "Se ha procesado correctamente");
        } catch (\Throwable $th) {


            Log::info($th);

            DB::rollback();

            return responseError($th, "Error al eliminar.... ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $purchase_order_id)
    {

        $purchase_order = PurchaseOrder::withCount('images')->find($purchase_order_id);

        if (!$purchase_order) {
            return responseError([], "Error al obtener el purchaseo_order x");
        }

        return responseOk($purchase_order, "Datos obtenidos con exito del purchaseoorder");
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
    public function update(Store $store, $purchase_order_id, Request $request)
    {
        // Implementa la lógica para actualizar un purchaseo existente
        // Esto podría implicar validar los datos de la solicitud,
        // actualizar el purchaseo en la base de datos y devolver el purchaseo actualizado.
        try {
            Log::info('updatex');
            Log::info($request->all());

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'supplier_id' => 'nullable',
                'observations' => ''
            ]);

            // $validatedData['store_id'] = $store->id;
            $validatedData['user_id']  = Auth::guard('api')->id();

            $purchase_order = PurchaseOrder::updateOrCreate(
                ['id' => $purchase_order_id],  // El campo 'id' indica si se actualiza o crea

                $validatedData
            );

            $purchase_order->load(['unit', 'supplier']);
            
            return responseOk($purchase_order, "Datos guardados correctamente update");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Error al guardar los datos del purchaseo desde purchase_order Private controller - > update", $th);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
