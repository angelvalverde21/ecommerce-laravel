<?php

namespace App\Services\Shopify\OrderImport;

use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShopifyOrderImportPlan
{
    public function forStore(Store $store): array
    {
        $required = ['orders', 'addresses', 'order_items', 'order_shopify_details', 'shopify_order_imports', 'shopify_order_import_rows'];
        $missing = array_values(array_filter($required, fn ($table) => ! Schema::hasTable($table)));
        $columns = Schema::hasTable('orders') ? Schema::getColumnListing('orders') : [];
        $orderColumns = [
            'store_id', 'user_id', 'shopify_order_id', 'shopify_order_name', 'variants',
            'currency_code', 'status', 'subtotal', 'igv', 'delivery_cost', 'total',
            'courier_address_id', 'delivery_type', 'is_cod', 'freight_collect',
        ];
        $missingColumns = array_values(array_diff($orderColumns, $columns));
        $addressReady = Schema::hasColumns('addresses', ['purpose', 'country_code', 'city', 'province', 'postal_code']);
        $ready = ! $missing && ! $missingColumns && $addressReady;
        $bound = config('shopify.order_import_store_slug') === $store->slug;
        $configured = filled(config('shopify.store')) && filled(config('shopify.version')) && filled(config('shopify.token'));

        return [
            'store' => $store->only(['id', 'name', 'slug']),
            'enabled' => (bool) config('shopify.order_import_enabled', false),
            'schema_ready' => $ready,
            'checks' => [
                ['label' => 'Estructura local de pedidos y datos relacionados', 'ready' => $ready],
                ['label' => 'Conexión Shopify configurada', 'ready' => $configured],
                ['label' => 'Vinculación local de Shopify con esta tienda', 'ready' => $bound],
                ['label' => 'Ejecución habilitada', 'ready' => (bool) config('shopify.order_import_enabled', false)],
            ],
            'missing_tables' => $missing,
            'missing_order_columns' => $missingColumns,
            'history_permission' => 'read_all_orders y read_orders o write_orders; se comprueban al ejecutar.',
            'tables' => $required,
            'command' => 'php artisan shopify:orders-import '.$store->slug.' --execute',
            'runs' => Schema::hasTable('shopify_order_imports')
                ? DB::table('shopify_order_imports')->where('store_id', $store->id)->orderByDesc('created_at')->limit(10)
                    ->get(['id', 'status', 'imported_orders', 'skipped_orders', 'unmatched_variants', 'unresolved_addresses', 'error_code', 'created_at', 'completed_at'])
                : [],
        ];
    }

    public function assertExecutable(Store $store): void
    {
        $plan = $this->forStore($store);
        foreach ($plan['checks'] as $check) {
            if (! $check['ready']) {
                throw new \RuntimeException('Importación detenida: '.$check['label'].'.');
            }
        }
    }
}
