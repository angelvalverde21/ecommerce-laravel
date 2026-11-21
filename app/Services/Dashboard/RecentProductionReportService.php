<?php

namespace App\Services\Dashboard;

use App\Models\Manufacture;
use App\Models\ManufactureVariant;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecentProductionReportService
{
    public function enrich(Store $store, Collection $rows): array
    {
        $ids = $rows->pluck('id');
        $financialAvailable = Schema::hasColumns('purchases', ['id', 'store_id', 'purchaseable_type', 'purchaseable_id'])
            && Schema::hasColumns('purchase_items', ['purchase_id', 'subtotal']);
        $expenses = $financialAvailable ? DB::table('purchases')
            ->join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.store_id', $store->id)
            ->where('purchases.purchaseable_type', (new Manufacture)->getMorphClass())
            ->whereIn('purchases.purchaseable_id', $ids)
            ->select('purchases.purchaseable_id')->selectRaw('SUM(purchase_items.subtotal) AS expense')
            ->groupBy('purchases.purchaseable_id')->pluck('expense', 'purchaseable_id') : collect();
        $detailsAvailable = Schema::hasTable('variants') && Schema::hasTable('products')
            && Schema::hasTable('variant_option_values') && Schema::hasTable('option_values') && Schema::hasTable('options');
        $details = $detailsAvailable ? ManufactureVariant::whereIn('manufacture_id', $ids)
            ->with(['variant.product', 'variant.optionValues.option'])->orderBy('id')->get()->groupBy('manufacture_id') : collect();

        return $rows->map(function (array $row) use ($expenses, $details, $financialAvailable, $detailsAvailable) {
            $expense = $financialAvailable ? round((float) $expenses->get($row['id'], 0), 2) : null;
            $row['total_expense'] = $expense;
            $row['unit_cost'] = $expense !== null && $row['planned_units'] > 0 ? round($expense / $row['planned_units'], 2) : null;
            $row['details_available'] = $detailsAvailable;
            $row['variants'] = $details->get($row['id'], collect())->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->variant?->product?->name ?? 'Producto no disponible',
                'variant_name' => $item->variant?->title,
                'sku' => $item->variant?->sku,
                'quantity' => (int) $item->quantity,
                'options' => $item->variant?->optionValues->map(fn ($value) => [
                    'name' => $value->option?->label ?? $value->option?->name ?? 'Variante', 'value' => $value->value,
                ])->values()->all() ?? [],
            ])->values()->all();

            return $row;
        })->all();
    }
}
