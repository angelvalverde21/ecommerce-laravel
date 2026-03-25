<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Store;
use App\Services\Dashboard\Purchase\PurchaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use function Illuminate\Log\log;

class PurchaseDashboardController extends Controller
{

    /**
     * Display a listing of the resource.
     */


    protected PurchaseService $purchaseService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->purchaseService = new PurchaseService();
    }

    public function index(Store $store)
    {
        //
        try {
            Log::info('exito index');
            //selectFields esta en el modelo purchase
            return responseOk($store->purchases()->orderBy('id', 'desc')->get(), "El listado de compras ha sido obtenido correctamente (dashboard)");
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
        Log::info($request);

        return respondePaginateOk($this->purchaseService->store($store, $request), "El purchase ha sido creado correctamente");

    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $purchase_id)
    {

        return responseOk($this->purchaseService->show($store, $purchase_id), "El purchase ha sido obtenido correctamente");
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
    public function update(Store $store, Request $request, $purchase_id)
    {

        return responseOk($this->purchaseService->update($store, $request, $purchase_id), "El purchase ha sido actualizado correctamente");
        
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($store, string $id)
    {

        try {

            $purchase = Purchase::findOrFail($id);

            $purchase->delete();

            return responseOk($purchase, "El purchase ha sido eliminado correctamente");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Ha sucedido un error interno al eliminar el purchase");
        }
    }
}
