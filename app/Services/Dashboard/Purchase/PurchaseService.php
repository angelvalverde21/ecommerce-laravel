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
            'production_stage_id' => 'sometimes|nullable|integer',
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
        $this->validateStage($store, $parentModel, $purchaseValidated, true);

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
        $this->validateStage($store, $parentModel, $validated, false);

        try {

            DB::beginTransaction();

            //Obteniendo el modelo padre
            $purchase = $parentModel->purchases()->findOrFail($purchase_id);

            //Actualizando con los datos recibidos

            unset($validated['purchaseable_type'], $validated['purchaseable_id']);

            $purchase->update($validated);

            // validar items
            $purchaseItems = $this->purchaseItemsData($request);

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
        $purchase = $store->purchases()->with(['supplier.user', 'items.unit', 'payments.images'])->findOrFail($purchase_id);
        return $purchase;
    }

    private function purchaseItemsData(Request $request): array
    {
        return collect($this->purchaseItemsValidate($request))
            ->map(function ($item) {
                $item['subtotal'] = round($item['quantity'] * $item['price'], 2);
                return $item;
            })
            ->toArray();
    }

    private function validateStage(Store $store, Model $parent, array $data, bool $creating): void
    {
        abort_unless((int) $parent->store_id === (int) $store->id, 404);
        $isProduction = $parent instanceof \App\Models\Manufacture && $parent->type === 'production';
        if ($isProduction && ($creating || array_key_exists('production_stage_id', $data)) && empty($data['production_stage_id'])) {
            throw ValidationException::withMessages(['production_stage_id' => 'Selecciona la etapa de producción.']);
        }
        if (!empty($data['production_stage_id']) && (!$isProduction || !$parent->stages()->whereKey($data['production_stage_id'])->exists())) {
            throw ValidationException::withMessages(['production_stage_id' => 'La etapa no pertenece a esta producción.']);
        }
    }
}
