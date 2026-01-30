<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Store;
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

    protected function getParentModel(array $validated): Model
    {
        $map = [
            'manufacture' => \App\Models\Manufacture::class,
            // 'order' => \App\Models\Order::class,
            // 'purchase' => \App\Models\Purchase::class,
        ];

        if (! isset($map[$validated['model_type']])) {
            throw ValidationException::withMessages([
                'model_type' => 'Tipo de modelo no válido',
            ]);
        }

        return $map[$validated['model_type']]::findOrFail($validated['model_id']);
    }

    
    public function store(Store $store, Request $request, $purchase_id)
    {

        $validated = $request->validate([
            'model_type' => [
                'required',
                Rule::in(['manufacture']) // Agrega más tipos según sea necesario
            ],
            'model_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'unit_id' => 'required|integer|exists:units,id',
            'price' => 'required|numeric',
            'total' => 'required|numeric',
            'section_id' => 'integer|exists:sections,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'observations' => 'nullable|string',
        ]);

        $purchase = Purchase::findOrFail($purchase_id);

        try {

            DB::beginTransaction();

            $purchase->update([
                'name' => $validated['name'],
                'quantity' => $validated['quantity'],
                'unit_id' => $validated['unit_id'],
                'price' => $validated['price'],
                'total' => $validated['total'],
                'section_id' => $validated['section_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'observations' => $validated['observations'] ?? null,
                'user_id' => Auth::guard('api')->id(),
                // No agregues store_id ni section_id aquí si es polimórfico
            ]);

            // return redirect()->route('erp.purchases.edit', ['store' => $this->store, 'purchase' => $purchase]);

            DB::commit();

            return responseOk($purchase, "se creo correctamente el purchase");

        } catch (\Throwable $th) {

            DB::rollback();

            Log::info($th);

            return responseError("Ha sucedido un error interno al crear el purchase");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $purchase_id) {}

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
    public function update(Store $store, Request $request)
    {

        $validated = $request->validate([
            'model_type' => [
                'required',
                Rule::in(['manufacture']) // Agrega más tipos según sea necesario
            ],
            'model_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'unit_id' => 'required|integer|exists:units,id',
            'price' => 'required|numeric',
            'total' => 'required|numeric',
            'section_id' => 'integer|exists:sections,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'observations' => 'nullable|string',
        ]);

        $parentModel = $this->getParentModel($validated);

        try {

            DB::beginTransaction();

            $purchase = $parentModel->purchases()->create([
                'name' => $validated['name'],
                'quantity' => $validated['quantity'],
                'unit_id' => $validated['unit_id'],
                'price' => $validated['price'],
                'total' => $validated['total'],
                'section_id' => $validated['section_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'observations' => $validated['observations'] ?? null,
                'user_id' => Auth::guard('api')->id(),
                // No agregues store_id ni section_id aquí si es polimórfico
            ]);

            // return redirect()->route('erp.purchases.edit', ['store' => $this->store, 'purchase' => $purchase]);

            DB::commit();

            return responseOk($purchase, "se creo correctamente el purchase");
        } catch (\Throwable $th) {

            DB::rollback();

            Log::info($th);

            return responseError("Ha sucedido un error interno al crear el purchase");
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
