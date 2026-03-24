<?php

namespace App\Services\Dashboard\Purchase;

use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'production' => \App\Models\Production::class,
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

    public function purchaseValidate(Store $store, Request $request): array
    {
        $validated = $request->validate([
            'purchaseable_type' => [
                'required',
                Rule::in(['manufacture', 'production'])
            ],
            'purchaseable_id' => 'required|integer',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'observations' => 'nullable|string',
            'purchase_start' => 'nullable|date',
            'purchase_end' => 'nullable|date',
        ]);

        return array_merge($validated, [
            'user_id' => Auth::guard('api')->id(),
            'store_id' => $store->id,
        ]);
    }


    public function purchaseItemsValidate(Request $request): array
    {
        $validated = $request->validate([
            'purchase_items' => 'required|array',
            'purchase_items.*.name' => 'required|string|max:255',
            'purchase_items.*.quantity' => 'required|numeric|min:0.01',
            'purchase_items.*.unit_id' => 'required|integer|exists:units,id',
            'purchase_items.*.price' => 'required|numeric|min:0',
            'purchase_items.*.subtotal' => 'required|numeric|min:0',
        ]);

        return $validated['purchase_items'];
    }

    public function store(Store $store, Request $request)
    {
        //Validamos los campos del purchase
        $purchaseValidated = $this->purchaseValidate($store, $request);

        //Obtenemos el Modelo que quiere un purchase
        $parentModel = $this->getParentModel($purchaseValidated);

        try {

            DB::beginTransaction();

            $purchase = $parentModel->purchases()->create($purchaseValidated);

            //Se recalcula el total porsiacaso

            $purchaseItems = $this->purchaseItemsData($request);  //purchaseItemsData lo usamos para validar y recalcular el dato del total

            //Creamos los items del purchase
            $purchase->items()->createMany($purchaseItems);

            DB::commit();

            return $purchase->load(['supplier.user', 'items.unit']);
        } catch (\Throwable $th) {

            DB::rollBack();

            Log::error($th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            return responseError("Ha sucedido un error interno al crear el purchase");
        }
    }

    public function update(Store $store, Request $request, $purchase_id)
    {
        $validated = $this->purchaseValidate($store, $request);

        $parentModel = $this->getParentModel($validated);

        try {

            DB::beginTransaction();

            //Obteniendo el modelo padre
            $purchase = $parentModel->purchases()->findOrFail($purchase_id);

            //Actualizando con los datos recibidos

            unset($validated['purchaseable_type'], $validated['purchaseable_id']);

            $purchase->update($validated);

            // validar items
            $purchaseItems = $this->purchaseItemsValidate($request); //purchaseItemsData lo usamos para validar y recalcular el dato del total

            // eliminar items actuales
            $purchase->items()->delete();

            // crear items nuevos
            $purchase->items()->createMany($purchaseItems);

            DB::commit();

            return $purchase->load(['supplier.user', 'items.unit']);
            
        } catch (\Throwable $th) {

            DB::rollBack();

            Log::error($th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            return responseError("Ha sucedido un error interno al actualizar el purchase");
        }
    }

    public function show($store, $purchase_id)
    {
        $purchase = $store->purchases()->with(['supplier.user', 'items.unit'])->findOrFail($purchase_id);
        return $purchase;
    }

    private function purchaseItemsData(Request $request): array
    {
        return collect($this->purchaseItemsValidate($request))
            ->map(function ($item) {
                $item['total'] = $item['quantity'] * $item['price'];
                return $item;
            })
            ->toArray();
    }
}
