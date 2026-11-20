<?php

namespace App\Services\Inventory;

use App\Models\{InventoryReception, Manufacture, Store, Variant};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryReceptionService
{
    public function save(Store $store, Manufacture $source, int $userId, array $data): InventoryReception
    {
        $items = $this->normalize($data['items']);
        $hash = $this->hash($source, $data, $items);
        return DB::transaction(function () use ($store, $source, $userId, $data, $items, $hash) {
            $this->lockSource($source);
            $existing = InventoryReception::where('store_id', $store->id)->where('request_id', $data['request_id'])->first();
            if ($existing) {
                if ($existing->payload_hash !== $hash) $this->fail('request_id', 'Esta solicitud ya se utilizó con otros datos.');
                return $existing;
            }
            $receipt = InventoryReception::create([
                'number' => InventoryReception::nextNumber($source),
                'store_id' => $store->id, 'source_type' => $source->getMorphClass(), 'source_id' => $source->id,
                'request_id' => $data['request_id'], 'payload_hash' => $hash, 'received_on' => $data['received_on'],
                'notes' => $data['notes'] ?? null, 'received_by' => $userId,
                'status' => 'draft', 'revision' => 1, 'stock_applied' => false, 'is_historical' => false,
            ]);
            $this->saveItems($receipt, $source, $store, $items);
            return $receipt;
        }, 3);
    }

    public function update(Store $store, Manufacture $source, int $id, array $data): InventoryReception
    {
        return DB::transaction(function () use ($store, $source, $id, $data) {
            $this->lockSource($source);
            $receipt = $this->receipt($store, $source, $id);
            if ($receipt->status !== 'draft') $this->fail('status', 'Solo se pueden editar recepciones pendientes de ingreso.');
            $this->checkRevision($receipt, $data['revision']);
            $items = $this->normalize($data['items']);
            // Drafts have no ledger entries. Replacing their details cannot alter inventory.
            if ($receipt->kardexes()->exists()) $this->fail('status', 'La recepción ya tiene movimientos. Actualiza la pantalla.');
            $receipt->items()->delete();
            $this->saveItems($receipt, $source, $store, $items);
            $receipt->update([
                'received_on' => $data['received_on'], 'notes' => $data['notes'] ?? null,
                'payload_hash' => $this->hash($source, $data, $items), 'revision' => $receipt->revision + 1,
            ]);
            return $receipt;
        }, 3);
    }

    public function post(Store $store, Manufacture $source, int $id, int $userId, int $revision): InventoryReception
    {
        return DB::transaction(function () use ($store, $source, $id, $userId, $revision) {
            $this->lockSource($source);
            $receipt = $this->receipt($store, $source, $id);
            if ($receipt->status === 'posted') return $receipt;
            if ($receipt->status !== 'draft') $this->fail('status', 'Esta recepción ya fue revertida.');
            $this->checkRevision($receipt, $revision);
            $items = $receipt->items()->orderBy('variant_id')->get();
            if ($items->isEmpty()) $this->fail('items', 'Agrega al menos una variante antes de confirmar.');
            foreach ($items as $item) {
                $variant = $this->variant($source, $store, $item->variant_id);
                if ((int) $variant->product_id !== (int) $item->product_id) $this->fail('items', 'El producto cambió. Revisa y guarda nuevamente la recepción.');
                $source->kardexes()->create([
                    'store_id' => $store->id, 'product_id' => $item->product_id, 'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity, 'direction' => 'in', 'comment' => $item->comment,
                    'inventory_reception_item_id' => $item->id,
                ]);
                $variant->increment('stock', $item->quantity);
            }
            $receipt->update([
                'status' => 'posted', 'stock_applied' => true, 'posted_at' => now(),
                'posted_by' => $userId, 'revision' => $receipt->revision + 1,
            ]);
            return $receipt;
        }, 3);
    }

    public function reverse(Store $store, Manufacture $source, int $id, int $userId, string $reason): InventoryReception
    {
        return DB::transaction(function () use ($store, $source, $id, $userId, $reason) {
            $this->lockSource($source);
            $receipt = $this->receipt($store, $source, $id);
            if ($receipt->reversed_at) return $receipt;
            if ($receipt->status !== 'posted') $this->fail('status', 'Una recepción pendiente todavía no tiene ingresos que revertir.');
            foreach ($receipt->items()->orderBy('variant_id')->get() as $item) {
                $variant = Variant::withoutGlobalScopes()->whereKey($item->variant_id)
                    ->whereHas('product', fn ($q) => $q->where('store_id', $store->id))->lockForUpdate()->first();
                if (!$variant || ($receipt->stock_applied && $variant->stock < $item->quantity)) {
                    $this->fail('reason', 'No se puede revertir: falta una variante o el stock disponible es insuficiente.');
                }
                $originals = $item->kardexes()->where('direction', 'in')->whereNull('reverses_kardex_id')->get();
                if ((int) $originals->sum('quantity') !== $item->quantity) $this->fail('reason', 'El detalle no coincide con sus movimientos originales. Revisa la recepción.');
                foreach ($originals as $original) $source->kardexes()->create([
                    'store_id' => $store->id, 'product_id' => $variant->product_id, 'variant_id' => $variant->id,
                    'quantity' => $original->quantity, 'direction' => 'out', 'comment' => 'Reversión de recepción N.º '.$receipt->number,
                    'inventory_reception_item_id' => $item->id, 'reverses_kardex_id' => $original->id,
                ]);
                if ($receipt->stock_applied) $variant->decrement('stock', $item->quantity);
            }
            $receipt->update(['status' => 'reversed', 'reversed_at' => now(), 'reversed_by' => $userId, 'reversal_reason' => $reason, 'revision' => $receipt->revision + 1]);
            return $receipt;
        }, 3);
    }

    private function receipt(Store $store, Manufacture $source, int $id): InventoryReception
    {
        return InventoryReception::where('store_id', $store->id)->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->id)->lockForUpdate()->findOrFail($id);
    }
    private function lockSource(Manufacture $source): void { Manufacture::whereKey($source->id)->lockForUpdate()->firstOrFail(); }
    private function normalize(array $items): array
    {
        return collect($items)->sortBy('variant_id')->map(fn ($item) => [
            'variant_id' => (int) $item['variant_id'], 'quantity' => (int) $item['quantity'], 'comment' => $item['comment'] ?? 'Recepcion',
        ])->values()->all();
    }
    private function hash(Manufacture $source, array $data, array $items): string
    {
        return hash('sha256', json_encode([$source->id, $data['received_on'], $data['notes'] ?? null, $items]));
    }
    private function checkRevision(InventoryReception $receipt, int $revision): void
    {
        if ($receipt->revision !== $revision) $this->fail('revision', 'Otra persona modificó esta recepción. Actualiza la pantalla y revisa los cambios.');
    }
    private function variant(Manufacture $source, Store $store, int $id): Variant
    {
        $variant = Variant::whereKey($id)->whereHas('product', fn ($q) => $q->where('store_id', $store->id))->lockForUpdate()->first();
        if (!$variant || !$source->manufactureVariants()->where('variant_id', $id)->exists()) {
            $this->fail('items', 'Una variante no está disponible o no pertenece a este documento. Actualiza el listado.');
        }
        return $variant;
    }
    private function saveItems(InventoryReception $receipt, Manufacture $source, Store $store, array $items): void
    {
        foreach ($items as $input) {
            $variant = $this->variant($source, $store, $input['variant_id']);
            $receipt->items()->create([
                ...$input, 'product_id' => $variant->product_id, 'product_name' => $variant->product->name,
                'sku' => $variant->sku, 'options' => $variant->optionValues->map(fn ($value) => ['name' => $value->option?->name, 'value' => $value->value])->all(),
            ]);
        }
    }
    private function fail(string $field, string $message): never { throw ValidationException::withMessages([$field => $message]); }
}
