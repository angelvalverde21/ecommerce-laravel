<?php

namespace App\Services\Dashboard\Purchase;

use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseService
{

    protected function getParentModel(array $validated): Model
    {
        $map = [
            'manufacture' => \App\Models\Manufacture::class,
            // 'order' => \App\Models\Order::class,
            // 'purchase' => \App\Models\Purchase::class,
        ];

        if (! isset($map[$validated['purchaseable_type']])) {
            throw ValidationException::withMessages([
                'purchaseable_type' => 'Tipo de modelo no válido',
            ]);
        }

        return $map[$validated['purchaseable_type']]::findOrFail($validated['purchaseable_id']);
    }

    public function store(Store $store, Request $request)
    {
        $validated = $request->validate([
            'purchaseable_type' => [
                'required',
                Rule::in(['manufacture']) // Agrega más tipos según sea necesario
            ],
            'purchaseable_id' => 'required|integer',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'observations' => 'nullable|string',
            'purchase_start' => 'nullable|date',
            'purchase_end' => 'nullable|date',
        ]);

        $parentModel = $this->getParentModel($validated);

        try {

            DB::beginTransaction();

            $purchase = $parentModel->purchases()->create([

                'supplier_id' => $validated['supplier_id'] ?? null,
                'observations' => $validated['observations'] ?? null,
                'user_id' => Auth::guard('api')->id(),
                'purchase_start' => $validated['purchase_start'] ?? null,
                'purchase_end' => $validated['purchase_end'] ?? null,
                'store_id' => $store->id,
                // No agregues store_id ni section_id aquí si es polimórfico
            ]);

            // return redirect()->route('erp.purchases.edit', ['store' => $this->store, 'purchase' => $purchase]);

            DB::commit();

            return responseOk($purchase->load(['supplier.user', 'unit']), "se creo correctamente el purchase");
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);
            return responseError("Ha sucedido un error interno al crear el purchase");
        }
    }
}
