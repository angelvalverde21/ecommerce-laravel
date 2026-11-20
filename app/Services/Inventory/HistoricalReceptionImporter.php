<?php

namespace App\Services\Inventory;

use App\Models\{InventoryReception, Manufacture, Product, Variant};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HistoricalReceptionImporter
{
    public function run(?int $storeId = null, bool $dryRun = false): array
    {
        $result = ['receptions' => 0, 'movements' => 0, 'quantity' => 0, 'skipped' => []];
        $groups = $this->eligible()->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->select('store_id', 'kardexable_id', 'created_at', 'comment')->distinct()->orderBy('created_at')->get();
        foreach ($groups as $group) {
            try {
                $count = DB::transaction(function () use ($group, $dryRun) {
                    $source = Manufacture::where('store_id', $group->store_id)->whereIn('type', ['production', 'order'])
                        ->lockForUpdate()->find($group->kardexable_id);
                    if (!$source || !$group->created_at) throw ValidationException::withMessages(['history' => 'Origen o fecha no disponible.']);
                    $rows = $this->eligible()->where('store_id', $group->store_id)->where('kardexable_id', $source->id)
                        ->where('created_at', $group->created_at)->where('comment', $group->comment)->orderBy('id')->lockForUpdate()->get();
                    if ($rows->isEmpty()) return null;
                    foreach ($rows as $row) {
                        if (!$row->variant_id || !$row->product_id || $row->quantity < 1 || $row->quantity != (int) $row->quantity) {
                            throw ValidationException::withMessages(['history' => 'Identificadores o cantidades incompletos.']);
                        }
                        if (Product::whereKey($row->product_id)->where('store_id', '!=', $source->store_id)->exists()) {
                            throw ValidationException::withMessages(['history' => 'El producto pertenece a otra tienda.']);
                        }
                    }
                    foreach ($rows->groupBy('variant_id') as $variantId => $movements) {
                        $variant = Variant::withoutGlobalScopes()->find($variantId);
                        if ($movements->pluck('product_id')->unique()->count() !== 1 || ($variant && (int) $variant->product_id !== (int) $movements->first()->product_id)) {
                            throw ValidationException::withMessages(['history' => 'La variante tiene productos inconsistentes.']);
                        }
                    }
                    if (!$dryRun) {
                        $receipt = InventoryReception::create([
                            'number' => InventoryReception::nextNumber($source),
                            'store_id' => $source->store_id, 'source_type' => $source->getMorphClass(), 'source_id' => $source->id,
                            'request_id' => (string) Str::uuid(), 'payload_hash' => hash('sha256', $rows->pluck('id')->toJson()),
                            'received_on' => substr($group->created_at, 0, 10), 'received_by' => null,
                            'is_historical' => true, 'stock_applied' => false,
                            'status' => 'posted', 'posted_at' => $group->created_at,
                            'notes' => 'Reconstruida del kardex por origen, motivo y fecha/hora exacta. Responsable original no registrado. Los datos de producto disponibles se recuperaron del catálogo actual.',
                            'created_at' => $group->created_at,
                        ]);
                        foreach ($rows->groupBy('variant_id') as $variantId => $movements) {
                            $variant = Variant::withoutGlobalScopes()->with(['product', 'optionValues'])->find($variantId);
                            $original = $movements->first();
                            $product = Product::whereKey($original->product_id)->where('store_id', $source->store_id)->first();
                            $item = $receipt->items()->create([
                                'variant_id' => $variantId, 'product_id' => $original->product_id,
                                'product_name' => $product?->name ?? 'Producto #'.$original->product_id,
                                'sku' => $variant?->sku, 'options' => $variant?->optionValues->map(fn ($value) => ['name' => $value->option?->name, 'value' => $value->value])->all() ?? [],
                                'quantity' => $movements->sum('quantity'), 'comment' => $group->comment,
                            ]);
                            // Only link existing ledger entries. Never create movements or change stock here.
                            DB::table('kardexes')->whereIn('id', $movements->pluck('id'))->update(['inventory_reception_item_id' => $item->id]);
                        }
                    }
                    return ['movements' => $rows->count(), 'quantity' => (int) $rows->sum('quantity')];
                }, 3);
                if ($count) { $result['receptions']++; $result['movements'] += $count['movements']; $result['quantity'] += $count['quantity']; }
            } catch (ValidationException $error) {
                $result['skipped'][] = ['source_id' => $group->kardexable_id, 'created_at' => $group->created_at, 'reason' => $error->getMessage()];
            }
        }
        return $result;
    }

    private function eligible()
    {
        return DB::table('kardexes')->where('kardexable_type', (new Manufacture)->getMorphClass())
            ->where('direction', 'in')->whereNull('inventory_reception_item_id')
            ->whereIn('comment', ['Recepcion', 'Recepción', 'Correccion', 'Corrección']);
    }
}
