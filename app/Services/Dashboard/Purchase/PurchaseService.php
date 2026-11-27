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
            'photo_session' => \App\Models\PhotoSession::class,
            'project_process' => \App\Models\ProjectProcess::class,
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
        if (in_array($request->input('purchaseable_type'), ['photo_session', 'project_process'], true)) {
            $roles = $request->input('purchaseable_type') === 'photo_session' && $request->filled('photo_source_kind') ? ['master', 'ceo', 'admin', 'marketing'] : ['master', 'ceo', 'admin'];
            abort_unless($request->user()?->hasAnyRole($roles), 403);
        }
        $validated = $request->validate([
            'purchaseable_type' => [
                'required',
                Rule::in(['manufacture', 'production', 'photo_session', 'project_process'])
            ],
            'purchaseable_id' => 'required|integer',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'supplier_address_id' => 'nullable|integer',
            'observations' => 'nullable|string',
            'purchase_start' => 'nullable|date',
            'purchase_end' => 'nullable|date',
            'production_stage_id' => 'sometimes|nullable|integer',
            'delivery' => 'sometimes|array',
            'delivery.enabled' => 'required_with:delivery|boolean',
            'delivery.expected_on' => 'nullable|date_format:Y-m-d',
            'delivery.handed_over_on' => 'nullable|date_format:Y-m-d',
            'photo_source_kind' => 'sometimes|required|in:photographer,model,location,task,general',
            'photo_source_id' => 'required_with:photo_source_kind|integer|min:1',
        ]);

        if (!empty($validated['photo_source_kind'])) {
            abort_unless($validated['purchaseable_type'] === 'photo_session', 422, 'El origen debe pertenecer a una sesión fotográfica.');
            $session = \App\Models\PhotoSession::where('store_id', $store->id)->findOrFail($validated['purchaseable_id']);
            $source = app(\App\Services\PhotoSessions\PhotoPurchaseSource::class)->resolve($session, $validated['photo_source_kind'], (int) $validated['photo_source_id']);
            if (!$source['recipient_name']) {
                $request->validate(['supplier_id' => [$source['kind'] === 'general' ? 'nullable' : 'required', 'integer', Rule::exists('suppliers', 'id')->where('store_id', $store->id)]]);
                $supplier = !empty($validated['supplier_id']) ? \App\Models\Supplier::where('store_id', $store->id)->findOrFail($validated['supplier_id']) : null;
                $validated['photo_recipient_name'] = $supplier ? ($supplier->user?->name ?: $supplier->name) : null;
            } else {
                $validated['photo_recipient_name'] = $source['recipient_name'];
                $validated['supplier_id'] = null; $validated['supplier_address_id'] = null;
                $request->merge(['supplier_id' => null, 'supplier_address_id' => null]);
            }
        }

        if ($request->exists('supplier_id') || $request->exists('supplier_address_id')) {
            $validated['supplier_address_id'] = app(\App\Services\Dashboard\Supplier\SupplierAddressService::class)
                ->resolve($store, $validated['supplier_id'] ?? null, $validated['supplier_address_id'] ?? null);
        }
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
        $delivery = $purchaseValidated['delivery'] ?? [];
        unset($purchaseValidated['delivery']);

        try {

            DB::beginTransaction();

            if ($parentModel instanceof \App\Models\ProjectProcess) {
                $parentModel = \App\Models\ProjectProcess::where('store_id', $store->id)->whereKey($parentModel->id)->lockForUpdate()->firstOrFail();
                abort_unless($parentModel->source_type === 'other', 422, 'Registra la compra en el proceso original.');
            }

            if ($parentModel instanceof \App\Models\Manufacture && $parentModel->type === 'production') {
                $parentModel->newQuery()->whereKey($parentModel->id)->lockForUpdate()->firstOrFail();
                $this->validateStage($store, $parentModel, $purchaseValidated, true);
            }

            if ($parentModel instanceof \App\Models\Manufacture) {
                app(\App\Services\Dashboard\ProductionStageStateService::class)->synchronize($parentModel, [$purchaseValidated['production_stage_id'] ?? null]);
            }
            $purchase = $parentModel->purchases()->create($purchaseValidated);

            //Se recalcula el total porsiacaso

            $purchaseItems = $this->purchaseItemsData($request);  //purchaseItemsData lo usamos para validar y recalcular el dato del total

            //Creamos los items del purchase
            $purchase->items()->createMany($purchaseItems);

            if (($delivery['enabled'] ?? false) && !empty($delivery['expected_on'])) {
                app(\App\Services\Delivery\DeliveryTrackingService::class)->create($purchase, $delivery, Auth::guard('api')->user());
            }

            if ($parentModel instanceof \App\Models\Manufacture) {
                app(\App\Services\Dashboard\ProductionStageStateService::class)->synchronize($parentModel, [$purchase->production_stage_id]);
            }

            DB::commit();

            return $purchase->load(['supplier.user', 'items.unit', 'deliveryTracking'])->loadCount('attachments');
            
        } catch (\Throwable $th) {

            DB::rollBack();

            if ($th instanceof ValidationException) { throw $th; }

            Log::error($th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            return responseError("Ha sucedido un error interno al crear el purchase");
        }
    }

    public function update(Store $store, Request $request, $purchase_id)
    {
        $existing = \App\Models\Purchase::where('store_id', $store->id)->findOrFail($purchase_id);
        if ($existing->photo_source_kind) $request->merge(['photo_source_kind' => $existing->photo_source_kind, 'photo_source_id' => $existing->photo_source_id]);
        $validated = $this->purchaseValidate($store, $request);
        unset($validated['delivery']); // Delivery changes use their audited endpoint.

        $parentModel = $this->getParentModel($validated);
        $this->validateStage($store, $parentModel, $validated, false);

        try {

            DB::beginTransaction();

            //Obteniendo el modelo padre
            if ($parentModel instanceof \App\Models\Manufacture && $parentModel->type === 'production') {
                $parentModel->newQuery()->whereKey($parentModel->id)->lockForUpdate()->firstOrFail();
                $this->validateStage($store, $parentModel, $validated, false);
            }
            $purchase = $parentModel->purchases()->findOrFail($purchase_id);
            $stageIds = [$purchase->production_stage_id, $validated['production_stage_id'] ?? $purchase->production_stage_id];
            if ($parentModel instanceof \App\Models\Manufacture) {
                app(\App\Services\Dashboard\ProductionStageStateService::class)->synchronize($parentModel, $stageIds);
            }

            //Actualizando con los datos recibidos

            unset($validated['purchaseable_type'], $validated['purchaseable_id']);

            $purchase->update($validated);

            // validar items
            $purchaseItems = $this->purchaseItemsData($request);

            // eliminar items actuales
            $purchase->items()->delete();

            // crear items nuevos
            $purchase->items()->createMany($purchaseItems);

            if ($parentModel instanceof \App\Models\Manufacture) {
                app(\App\Services\Dashboard\ProductionStageStateService::class)->synchronize($parentModel, $stageIds);
            }
            DB::commit();

            return $purchase->load(['supplier.user', 'items.unit', 'deliveryTracking']);
            
        } catch (\Throwable $th) {

            DB::rollBack();

            Log::error($th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            if ($th instanceof ValidationException) { throw $th; }
            return responseError("Ha sucedido un error interno al actualizar el purchase");
        }
    }

    public function show($store, $purchase_id)
    {
        $purchase = $store->purchases()->withCount('attachments')->with(['supplier.user', 'items.unit', 'payments.images', 'deliveryTracking'])->findOrFail($purchase_id);
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
        if ($parent instanceof \App\Models\ProjectProcess && $parent->source_type !== 'other') {
            throw ValidationException::withMessages(['purchaseable_type' => 'Registra la compra en el proceso original.']);
        }
        $isProduction = $parent instanceof \App\Models\Manufacture && $parent->type === 'production';
        if ($isProduction && ($creating || array_key_exists('production_stage_id', $data)) && empty($data['production_stage_id'])) {
            throw ValidationException::withMessages(['production_stage_id' => 'Selecciona la etapa de producción.']);
        }
        if (!empty($data['production_stage_id']) && (!$isProduction || !\App\Models\ProductionStage::whereKey($data['production_stage_id'])->exists())) {
            throw ValidationException::withMessages(['production_stage_id' => 'Selecciona una sección válida del catálogo de producción.']);
        }
        if ($isProduction && !empty($data['production_stage_id'])
            && !app(\App\Services\Dashboard\ProductionStageService::class)->enabledIds($parent)->contains((int) $data['production_stage_id'])) {
            throw ValidationException::withMessages(['production_stage_id' => 'Selecciona una sección incluida en esta producción.']);
        }
    }
}
