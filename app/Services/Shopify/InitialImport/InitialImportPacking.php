<?php

namespace App\Services\Shopify\InitialImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

/** Read-only packing categories, derived from the imported Shopify statuses. */
class InitialImportPacking
{
    public function expression(): string
    {
        $grammar = DB::connection()->getQueryGrammar();
        $cancelled = $grammar->wrap('orders.payload->cancelledAt');
        $shipping = $grammar->wrap('orders.payload->displayFulfillmentStatus');
        $payment = $grammar->wrap('orders.payload->displayFinancialStatus');

        return "CASE
            WHEN $cancelled IS NOT NULL AND $cancelled != 'null' THEN 'cancelled'
            WHEN $shipping = 'FULFILLED' THEN 'shipped'
            WHEN $shipping = 'ON_HOLD' THEN 'on_hold'
            WHEN $shipping = 'SCHEDULED' THEN 'scheduled'
            WHEN $shipping IN ('IN_PROGRESS', 'PARTIALLY_FULFILLED') THEN 'preparing'
            WHEN $shipping = 'UNFULFILLED' AND $payment = 'PAID' THEN 'ready'
            ELSE 'review' END";
    }

    public function counts(Builder $query): array
    {
        $grouped = (clone $query)->selectRaw($this->expression().' AS packing_state, COUNT(*) AS amount')
            ->groupBy('packing_state')->get()->pluck('amount', 'packing_state')->map(fn ($count) => (int) $count)->all();

        return ['total' => array_sum($grouped), 'ready' => 0, 'preparing' => 0, 'shipped' => 0, ...$grouped];
    }
}
