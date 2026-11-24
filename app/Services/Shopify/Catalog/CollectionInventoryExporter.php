<?php

namespace App\Services\Shopify\Catalog;

use App\Models\{Collection, ShopifyProductExport};
use Illuminate\Support\Facades\{Cache, Crypt, DB, Schema};
use Illuminate\Support\Str;
use RuntimeException;

class CollectionInventoryExporter
{
    public function __construct(private CatalogClient $client) {}

    private function lines(Collection $collection, array $ids, int $percentage): array
    {
        sort($ids, SORT_NUMERIC);
        $rows = DB::table('manufacture_variant as mv')
            ->join('manufactures as m', 'm.id', '=', 'mv.manufacture_id')
            ->join('collections_processes as cp', function ($join) { $join->on('cp.source_id', '=', 'm.id')->where('cp.source_type', 'manufacture'); })
            ->join('variants as v', 'v.id', '=', 'mv.variant_id')->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('cp.collection_id', $collection->id)->where('cp.store_id', $collection->store_id)
            ->where('m.store_id', $collection->store_id)->whereIn('m.type', ['order', 'production'])
            ->where('p.store_id', $collection->store_id)->whereIn('v.id', $ids)
            ->groupBy('v.id', 'v.product_id', 'v.sku', 'v.shopify_variant_id', 'p.name')->orderBy('v.id')
            ->select(['v.id', 'v.product_id', 'v.sku', 'v.shopify_variant_id', 'p.name'])->selectRaw('SUM(mv.quantity) as planned_units')->get();
        abort_unless($rows->count() === count($ids), 422, 'Una variante ya no pertenece a las manufacturas de esta colección. Actualiza la selección.');
        return $rows->map(function ($row) use ($percentage) {
            $remote = (string) $row->shopify_variant_id;
            if (ctype_digit($remote)) $remote = 'gid://shopify/ProductVariant/'.$remote;
            abort_unless(preg_match('~^gid://shopify/ProductVariant/\d+$~D', $remote) && filled($row->sku), 422, 'La variante '.($row->sku ?: '#'.$row->id).' debe estar vinculada a Shopify antes de enviar su inventario.');
            $planned = max(0, (int) $row->planned_units);
            $quantity = intdiv($planned * $percentage, 100);
            abort_if($quantity > 2147483647, 422, 'La cantidad supera el límite de inventario de Shopify.');
            return ['variant_id' => (int) $row->id, 'product_id' => (int) $row->product_id, 'sku' => $row->sku, 'name' => $row->name,
                'remote_variant_id' => $remote, 'planned_units' => $planned, 'export_units' => $quantity];
        })->all();
    }

    private function connection(Collection $collection): string
    {
        abort_unless(DB::table('stores')->where('id', $collection->store_id)->value('slug') === config('shopify.catalog_store_slug'), 422, 'Esta tienda no está vinculada a la conexión Shopify configurada.');
        abort_unless(strcmp((string) config('shopify.catalog_version'), '2026-01') >= 0, 422, 'Configura una versión de la API Shopify de 2026-01 o posterior para enviar inventario.');
        return $this->client->domain();
    }

    public function preview(Collection $collection, int $actor, array $ids, int $percentage): array
    {
        $domain = $this->connection($collection);
        $lines = $this->lines($collection, $ids, $percentage);
        abort_unless(count(array_unique(array_column($lines, 'remote_variant_id'))) === count($lines), 422, 'Dos variantes locales apuntan a la misma variante Shopify. Revisa sus vínculos.');
        $access = $this->client->request('query CollectionInventoryAccess { shop { myshopifyDomain } currentAppInstallation { accessScopes { handle } } location { id name isActive fulfillsOnlineOrders } }');
        abort_unless(strtolower($access['shop']['myshopifyDomain'] ?? '') === $domain && in_array('write_inventory', array_column($access['currentAppInstallation']['accessScopes'] ?? [], 'handle'), true), 422, 'Shopify requiere write_inventory en la tienda configurada.');
        $location = $access['location'] ?? [];
        abort_unless(preg_match('~^gid://shopify/Location/\d+$~D', $location['id'] ?? '') && ($location['isActive'] ?? false) && ($location['fulfillsOnlineOrders'] ?? false), 422, 'La sucursal principal de Shopify debe estar activa y habilitada para pedidos online.');
        $nodes = [];
        foreach (array_chunk(array_column($lines, 'remote_variant_id'), 100) as $batch) {
            $data = $this->client->request('query CollectionInventoryLevels($ids: [ID!]!, $location: ID!) { nodes(ids: $ids) { ... on ProductVariant { id sku product { id } inventoryItem { id tracked inventoryLevel(locationId: $location) { quantities(names: ["available"]) { name quantity } } } } } }', ['ids' => $batch, 'location' => $location['id']]);
            foreach ($data['nodes'] ?? [] as $node) if (isset($node['id'])) $nodes[$node['id']] = $node;
        }
        $exports = Schema::hasTable('shopify_product_exports') ? ShopifyProductExport::where('store_id', $collection->store_id)
            ->where('shop_domain', $domain)->where('status', 'synced')->whereIn('product_id', array_column($lines, 'product_id'))->get()->keyBy('product_id') : collect();
        $items = [];
        foreach ($lines as $line) {
            $node = $nodes[$line['remote_variant_id']] ?? [];
            $item = $node['inventoryItem'] ?? [];
            $available = collect($item['inventoryLevel']['quantities'] ?? [])->firstWhere('name', 'available');
            $export = $exports->get($line['product_id']);
            $binding = $export?->variant_links[$line['variant_id']] ?? null;
            // Manually created products can have no SKU. Accept only a persisted,
            // exact product AND variant association from the approved reconciliation.
            $approvedManual = blank($node['sku'] ?? null) && $export
                && $export->remote_product_id === ($node['product']['id'] ?? null)
                && ($binding['variant_id'] ?? null) === $line['remote_variant_id'];
            abort_unless((($node['sku'] ?? null) === $line['sku'] || $approvedManual) && preg_match('~^gid://shopify/InventoryItem/\d+$~D', $item['id'] ?? '') && ($item['tracked'] ?? false) && isset($available['quantity']) && is_int($available['quantity']), 422, 'Revisa el vínculo, el seguimiento de inventario y la sucursal del SKU '.$line['sku'].' en Shopify.');
            $items[] = $line + ['inventory_item_id' => $item['id'], 'current_units' => $available['quantity']];
        }
        $approved = ['action' => 'collection_inventory', 'store' => $collection->store_id, 'collection' => $collection->id, 'actor' => $actor,
            'domain' => $domain, 'location' => $location['id'], 'percentage' => $percentage, 'items' => $items,
            'hash' => hash('sha256', json_encode($lines)), 'key' => (string) Str::uuid(), 'expires' => now()->addMinutes(15)->timestamp];
        return ['domain' => $domain, 'location_name' => $location['name'] ?? 'Principal', 'percentage' => $percentage,
            'items' => array_map(fn ($item) => collect($item)->except(['remote_variant_id', 'inventory_item_id'])->all(), $items),
            'token' => Crypt::encryptString(json_encode($approved))];
    }

    public function send(Collection $collection, int $actor, string $token): array
    {
        try { $approved = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { abort(422, 'La revisión no es válida. Revisa nuevamente el envío.'); }
        abort_unless(($approved['action'] ?? '') === 'collection_inventory' && ($approved['store'] ?? null) === $collection->store_id
            && ($approved['collection'] ?? null) === $collection->id && ($approved['actor'] ?? null) === $actor
            && ($approved['domain'] ?? '') === $this->connection($collection) && ($approved['expires'] ?? 0) > now()->timestamp, 422, 'La revisión venció o cambió la conexión. Revisa nuevamente el envío.');
        $cacheKey = 'collection-inventory:'.hash('sha256', $token);
        $lock = Cache::lock('shopify-inventory:'.$approved['domain'], 90);
        abort_unless($lock->get(), 409, 'Hay otro envío de inventario en curso. Espera antes de reintentar.');
        try {
            if ($result = Cache::get($cacheKey)) return $result;
            $lines = $this->lines($collection, array_column($approved['items'], 'variant_id'), $approved['percentage']);
            abort_unless(hash_equals($approved['hash'], hash('sha256', json_encode($lines))), 409, 'Las unidades previstas o los vínculos cambiaron. Revisa nuevamente el envío.');
            $modern = strcmp((string) config('shopify.catalog_version'), '2026-04') >= 0;
            $quantities = array_map(fn ($item) => ['inventoryItemId' => $item['inventory_item_id'], 'locationId' => $approved['location'],
                'quantity' => $item['export_units'], $modern ? 'changeFromQuantity' : 'compareQuantity' => $item['current_units']], $approved['items']);
            $data = $this->client->request('mutation SendCollectionInventory($input: InventorySetQuantitiesInput!, $key: String!) { inventorySetQuantities(input: $input) @idempotent(key: $key) { inventoryAdjustmentGroup { createdAt } userErrors { field message } } }',
                ['input' => ['name' => 'available', 'reason' => 'correction', 'referenceDocumentUri' => 'erp://collections/'.$collection->id.'/inventory/'.$approved['key'], 'quantities' => $quantities], 'key' => $approved['key']]);
            $result = $data['inventorySetQuantities'] ?? [];
            if (!empty($result['userErrors']) || empty($result['inventoryAdjustmentGroup'])) throw new RuntimeException('Shopify no confirmó el cambio de stock. '.mb_substr(implode(' ', array_column($result['userErrors'] ?? [], 'message')), 0, 1000));
            $result = ['sent_variants' => count($quantities), 'sent_units' => array_sum(array_column($quantities, 'quantity')), 'message' => 'Stock confirmado en Shopify.'];
            Cache::put($cacheKey, $result, now()->addDay());
            return $result;
        } finally { $lock->release(); }
    }
}
