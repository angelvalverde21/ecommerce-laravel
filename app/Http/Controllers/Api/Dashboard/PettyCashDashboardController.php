<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PettyCashDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
        $pettyCash = $store->pettyCashes()->with(['employee.user', 'gateway'])->get();

        return responseOk($pettyCash, 'Caja chica obtenida correctamente');
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
        try {

            DB::beginTransaction();

            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                // 'amount_assigned' => 'required|numeric|min:0',
                // 'gateway_id' => 'required|exists:gateways,id',
            ]);

            $pettyCash = $store->pettyCashes()->create(
                [
                    'employee_id' => $request->employee_id,
                    // 'gateway_id' => $request->gateway_id,
                    'amount_assigned' => 0,
                    'balance' => 0,
                    'opened_at' => now(),
                ]
            );

            // $pettyCash->payments()->create(
            //     [
            //         'store_id' => $store->id,
            //         'user_id' => Auth::id(),
            //         'amount' => $request->amount_assigned,
            //         'gateway_id' => $request->gateway_id,
            //         'status' => 'paid',
            //         'date' => now(),
            //         'comment' => 'Apertura de caja chica',
            //         'direction' => 'in',
            //     ]
            // );

            DB::commit();

            return responseOk($pettyCash, "Se ha procesado correctamente");
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return responseError("Error al eliminar.... ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $petty_cash_id)
    {
        //

        try {
        
            $petty_cash = $store->pettyCashes()->with(['employee.user', 'gateway', 'payments.images'])->findOrFail($petty_cash_id);
        
            return responseOk($petty_cash, "Se ha procesado correctamente");
        
        } catch (\Throwable $th) {
        
            Log::info($th);
        
            return responseError("Error al eliminar.... ");
        
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
    public function update(Request $request, Store $store, $petty_cash_id)
    {
        //
        try {
        
            DB::beginTransaction();

            $petty_cash = $store->pettyCashes()->findOrFail($petty_cash_id);

            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'amount_assigned' => 'required|numeric|min:0',
                'gateway_id' => 'required|exists:gateways,id',
            ]);

            $petty_cash->update(
                [
                    'employee_id' => $request->employee_id,
                    'gateway_id' => $request->gateway_id,
                    'amount_assigned' => $request->amount_assigned,
                ]
            );

            DB::commit();

            return responseOk($petty_cash, "Se ha actualizado correctamente la caja chica");

        } catch (\Throwable $th) {
        
            Log::info($th);
        
            DB::rollback();

            return responseError("Error actualizar la caja chica.... ");

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
