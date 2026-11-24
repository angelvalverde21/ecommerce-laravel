<?php
// Operational reconciliation of the explicitly reviewed November launch products.
// prepare/verify use only Shopify queries. delete/link require --apply-approved.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Product, Store, ShopifyProductExport};
use App\Services\Shopify\Catalog\CatalogClient;
use Illuminate\Support\Facades\{DB, Schema};

$mode = $argv[1] ?? 'prepare';
$directory = storage_path('app/private/backups/shopify-reconciliation-2026-11-23');
$seed = json_decode(file_get_contents($directory.'/approved-pairs.json'), true, 512, JSON_THROW_ON_ERROR);
$client = app(CatalogClient::class);
if ($client->domain() !== 'sorelleperu.myshopify.com' || config('shopify.catalog_store_slug') !== 'sorelle') throw new RuntimeException('Unexpected Shopify connection.');
$store = Store::where('slug', 'sorelle')->firstOrFail();
if (count($seed) !== 33 || array_column($seed, 'erp_id') !== range(766, 798)) throw new RuntimeException('Unexpected approved product set.');
if (in_array('gid://shopify/Product/15638176923872', array_column($seed, 'target_id'), true)) throw new RuntimeException('The unrelated test product is protected.');

function saveFile(string $path, mixed $data): void { file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX); }
function loadFile(string $path): array { return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
function normalizedOption(string $value): string {
    $value = mb_strtolower(trim(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)));
    $value = preg_replace('/^fit\s+/', '', $value);
    return in_array($value, ['standar', 'standard', 'estandar'], true) ? 'estandar' : $value;
}
function fingerprint(string $table, array $except = []): array {
    $columns = array_values(array_diff(Schema::getColumnListing($table), $except));
    $rows = DB::table($table)->orderBy('id')->get($columns)->all();
    return ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
}
function integrity(): array {
    $result = ['products' => fingerprint('products', ['name', 'origin', 'online_store_url', 'updated_at']),
        'variants' => fingerprint('variants', ['shopify_variant_id', 'updated_at'])];
    foreach (['manufacture_variant', 'variant_option_values', 'option_values', 'options', 'collections_processes'] as $table) if (Schema::hasTable($table)) $result[$table] = fingerprint($table);
    return $result;
}
function readProducts(array $ids, bool $full = false): array {
    global $client;
    $fields = 'id title status handle onlineStoreUrl metafield(namespace: "erp", key: "catalog_export") { value } variants(first: 10) { nodes { id title sku selectedOptions { name value } '.($full ? 'price compareAtPrice inventoryQuantity inventoryItem { id tracked inventoryLevels(first: 5) { nodes { id location { id name } quantities(names: ["available", "on_hand"]) { name quantity } } pageInfo { hasNextPage } } }' : '').' } pageInfo { hasNextPage } }';
    if ($full) $fields .= ' descriptionHtml vendor productType tags options { id name values } images(first: 20) { nodes { id url altText } pageInfo { hasNextPage } }';
    $result = [];
    foreach (array_chunk(array_values(array_unique(array_filter($ids))), $full ? 3 : 30) as $batch) {
        $data = $client->request('query ReconciliationProducts($ids: [ID!]!) { nodes(ids: $ids) { ... on Product { '.$fields.' } } }', ['ids' => $batch]);
        if (!isset($data['nodes']) || count($data['nodes']) !== count($batch)) throw new RuntimeException('Incomplete Shopify node response.');
        foreach ($data['nodes'] as $node) {
            if (!$node) continue;
            if ($node['variants']['pageInfo']['hasNextPage']) throw new RuntimeException('Unexpected extra variants: '.$node['id']);
            if ($full && ($node['images']['pageInfo']['hasNextPage'] || collect($node['variants']['nodes'])->contains(fn ($v) => $v['inventoryItem']['inventoryLevels']['pageInfo']['hasNextPage']))) throw new RuntimeException('Backup pagination is incomplete.');
            $result[$node['id']] = $node;
        }
        if ($full) echo 'Backed up '.count($result)." products\n";
    }
    return $result;
}
function checkOrders(array $sourceIds): array {
    global $client;
    $after = null; $checked = 0; $references = [];
    do {
        $data = $client->request('query ReconciliationOrders($after: String) { orders(first: 20, after: $after, query: "created_at:>=2026-11-20", sortKey: CREATED_AT) { nodes { id name lineItems(first: 20) { nodes { product { id } variant { id } } pageInfo { hasNextPage endCursor } } } pageInfo { hasNextPage endCursor } } }', ['after' => $after]);
        $page = $data['orders'] ?? null;
        if (!isset($page['nodes'], $page['pageInfo'])) throw new RuntimeException('Order audit incomplete.');
        foreach ($page['nodes'] as $order) {
            $checked++;
            $lines = $order['lineItems'];
            while (true) {
                foreach ($lines['nodes'] as $line) if (in_array($line['product']['id'] ?? '', $sourceIds, true)) $references[] = ['order' => $order['id'], 'product' => $line['product']['id']];
                if (!$lines['pageInfo']['hasNextPage']) break;
                $more = $client->request('query ReconciliationOrderLines($id: ID!, $after: String) { order(id: $id) { lineItems(first: 100, after: $after) { nodes { product { id } variant { id } } pageInfo { hasNextPage endCursor } } } }', ['id' => $order['id'], 'after' => $lines['pageInfo']['endCursor']]);
                $lines = $more['order']['lineItems'] ?? throw new RuntimeException('Order lines incomplete.');
            }
        }
        $next = $page['pageInfo']['endCursor'] ?? null;
        if ($page['pageInfo']['hasNextPage'] && (!$next || $next === $after)) throw new RuntimeException('Order cursor is incomplete.');
        $after = $next;
    } while ($page['pageInfo']['hasNextPage']);
    if ($references) throw new RuntimeException('A draft has orders. No deletion permitted: '.json_encode($references));
    return ['orders_checked' => $checked, 'draft_references' => 0];
}
function bindingOptions(array $values): string { $values = array_map('normalizedOption', $values); sort($values); return implode('|', $values); }

if ($mode === 'prepare') {
    $run = $directory.'/run-'.gmdate('Ymd-His');
    if (is_dir($run)) throw new RuntimeException('Backup directory exists.');
    mkdir($run, 0700, true);
    $ids = array_column($seed, 'erp_id');
    $local = ['products' => DB::table('products')->where('store_id', $store->id)->whereIn('id', $ids)->orderBy('id')->get()->all(),
        'variants' => DB::table('variants')->whereIn('product_id', $ids)->orderBy('id')->get()->all(),
        'exports' => DB::table('shopify_product_exports')->where('store_id', $store->id)->whereIn('product_id', $ids)->orderBy('product_id')->get()->all()];
    if (count($local['products']) !== 33 || count($local['exports']) !== 33) throw new RuntimeException('The local publication changed.');
    saveFile($run.'/local-before.json', $local);
    $baseline = integrity(); saveFile($run.'/integrity-before.json', $baseline);
    $exports = collect($local['exports'])->keyBy('product_id');
    $remote = readProducts(array_merge(array_column($seed, 'target_id'), array_column($local['exports'], 'remote_product_id'), ['gid://shopify/Product/15638176923872']), true);
    saveFile($run.'/shopify-before.json', $remote);
    $pairs = []; $sources = []; $seenVariants = [];
    foreach ($seed as $approved) {
        $id = $approved['erp_id']; $target = $remote[$approved['target_id']] ?? throw new RuntimeException('Manual product missing: '.$id);
        if ($target['title'] !== $approved['target_title'] || $target['status'] !== ($id === 784 ? 'DRAFT' : 'ACTIVE') || $target['metafield']) throw new RuntimeException('Manual product identity changed: '.$id);
        if (DB::table('products')->where('store_id', $store->id)->where('origin', $target['id'])->where('id', '<>', $id)->exists()) throw new RuntimeException('The manual product already belongs to another ERP product: '.$id);
        $variants = collect($local['variants'])->where('product_id', $id)->where('status', 1);
        if ($variants->count() !== count($target['variants']['nodes'])) throw new RuntimeException('Variant counts differ: '.$id);
        $bindings = [];
        foreach ($variants as $variant) {
            $values = DB::table('variant_option_values as x')->join('option_values as v', 'v.id', '=', 'x.option_value_id')->where('x.variant_id', $variant->id)->pluck('v.value')->all();
            $matches = collect($target['variants']['nodes'])->filter(fn ($node) => bindingOptions(array_column($node['selectedOptions'], 'value')) === bindingOptions($values))->values();
            if ($matches->count() !== 1 || isset($seenVariants[$matches[0]['id']])) throw new RuntimeException('Ambiguous variant binding: '.$variant->id);
            if (DB::table('variants')->where('shopify_variant_id', $matches[0]['id'])->where('id', '<>', $variant->id)->exists()) throw new RuntimeException('A Shopify variant is already bound elsewhere.');
            $seenVariants[$matches[0]['id']] = true;
            $bindings[$variant->id] = ['variant_id' => $matches[0]['id'], 'sku' => $matches[0]['sku'], 'options' => collect($matches[0]['selectedOptions'])->pluck('value', 'name')->all()];
        }
        $export = $exports[$id]; $source = $remote[$export->remote_product_id] ?? null;
        if ($source) {
            if ($source['status'] !== 'DRAFT' || ($source['metafield']['value'] ?? null) !== $export->id || $source['id'] === $target['id'] || $source['id'] === 'gid://shopify/Product/15638176923872') throw new RuntimeException('Source is not a verified ERP draft: '.$id);
            $sources[] = ['erp_id' => $id, 'id' => $source['id'], 'export_id' => $export->id, 'title' => $source['title']];
        } elseif (!in_array($id, [770, 794], true)) throw new RuntimeException('Unexpected missing original: '.$id);
        $pairs[] = ['erp_id' => $id, 'target_id' => $target['id'], 'name' => $target['title'], 'status' => $target['status'], 'handle' => $target['handle'], 'online_store_url' => $target['onlineStoreUrl'], 'bindings' => $bindings];
    }
    if (count($sources) !== 31 || count($seenVariants) !== 84) throw new RuntimeException('The approved reconciliation counts changed.');
    $orders = checkOrders(array_column($sources, 'id')); saveFile($run.'/order-audit.json', $orders);
    $plan = ['domain' => $client->domain(), 'store_id' => $store->id, 'run' => $run, 'sources' => $sources, 'pairs' => $pairs, 'seed_sha256' => hash_file('sha256', $directory.'/approved-pairs.json'), 'prepared_at' => now()->toIso8601String()];
    saveFile($run.'/plan.json', $plan); saveFile($directory.'/current-plan.json', $plan);
    echo json_encode(['prepared' => true, 'run' => $run, 'delete_drafts' => count($sources), 'link_products' => count($pairs), 'link_variants' => count($seenVariants), 'order_audit' => $orders], JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit;
}

$plan = loadFile($directory.'/current-plan.json');
if ($plan['domain'] !== $client->domain() || $plan['store_id'] !== $store->id || $plan['seed_sha256'] !== hash_file('sha256', $directory.'/approved-pairs.json')) throw new RuntimeException('The approved plan or connection changed.');
$run = $plan['run'];
if (in_array($mode, ['delete', 'link'], true) && !in_array('--apply-approved', $argv, true)) throw new RuntimeException('Explicit --apply-approved is required.');

if ($mode === 'delete') {
    checkOrders(array_column($plan['sources'], 'id'));
    foreach ($plan['sources'] as $source) {
        $current = readProducts([$source['id']])[$source['id']] ?? null;
        if ($current) {
            if ($current['status'] !== 'DRAFT' || $current['title'] !== $source['title'] || ($current['metafield']['value'] ?? null) !== $source['export_id']) throw new RuntimeException('A draft changed. Stopping before deletion: '.$source['id']);
            file_put_contents($run.'/deletion-journal.jsonl', json_encode(['id' => $source['id'], 'state' => 'requested', 'at' => now()->toIso8601String()])."\n", FILE_APPEND | LOCK_EX);
            $client->delete($source['id']);
        }
        DB::transaction(function () use ($source, $store) {
            $export = ShopifyProductExport::where('store_id', $store->id)->whereKey($source['export_id'])->lockForUpdate()->firstOrFail();
            if ($export->remote_product_id !== $source['id'] || $export->product_id !== $source['erp_id']) throw new RuntimeException('Local export changed.');
            foreach ($export->variant_links ?? [] as $id => $link) DB::table('variants')->where('product_id', $export->product_id)->where('id', $id)->where('shopify_variant_id', $link['variant_id'])->update(['shopify_variant_id' => null]);
            $export->update(['status' => 'reconciling', 'last_error' => 'Borrador eliminado por conciliación aprobada; pendiente de vincular al producto manual.']);
        });
        file_put_contents($run.'/deletion-journal.jsonl', json_encode(['id' => $source['id'], 'state' => 'confirmed_deleted', 'at' => now()->toIso8601String()])."\n", FILE_APPEND | LOCK_EX);
        echo 'Deleted ERP draft for product '.$source['erp_id'].PHP_EOL;
    }
    if (readProducts(array_column($plan['sources'], 'id'))) throw new RuntimeException('At least one original is still present.');
    saveFile($run.'/deleted.json', ['confirmed' => count($plan['sources']), 'at' => now()->toIso8601String()]);
    echo "All 31 approved originals are absent.\n"; exit;
}

if ($mode === 'link') {
    if (!is_file($run.'/deleted.json') || readProducts(array_column($plan['sources'], 'id'))) throw new RuntimeException('Finish deleting the approved originals first.');
    $targets = readProducts(array_column($plan['pairs'], 'target_id'));
    foreach ($plan['pairs'] as $pair) {
        $target = $targets[$pair['target_id']] ?? throw new RuntimeException('Manual destination is missing.');
        if ($target['title'] !== $pair['name'] || $target['status'] !== $pair['status']) throw new RuntimeException('Manual destination changed.');
        if (array_diff(array_column($pair['bindings'], 'variant_id'), array_column($target['variants']['nodes'], 'id'))) throw new RuntimeException('Manual variant IDs changed.');
    }
    DB::transaction(function () use ($plan, $store, $run) {
        foreach ($plan['pairs'] as $pair) {
            $product = DB::table('products')->where('store_id', $store->id)->where('id', $pair['erp_id'])->lockForUpdate()->first();
            if (!$product) throw new RuntimeException('ERP product missing.');
            DB::table('products')->where('id', $product->id)->update(['name' => $pair['name'], 'origin' => $pair['target_id'], 'online_store_url' => $pair['online_store_url'], 'updated_at' => now()]);
            foreach ($pair['bindings'] as $id => $binding) {
                if (!DB::table('variants')->where('product_id', $product->id)->where('id', $id)->exists()) throw new RuntimeException('ERP variant moved.');
                DB::table('variants')->where('product_id', $product->id)->where('id', $id)->update(['shopify_variant_id' => $binding['variant_id'], 'updated_at' => now()]);
            }
            $export = ShopifyProductExport::where('store_id', $store->id)->where('product_id', $product->id)->lockForUpdate()->firstOrFail();
            $export->update(['status' => 'synced', 'remote_product_id' => $pair['target_id'], 'remote_status' => $pair['status'], 'handle' => $pair['handle'], 'variant_links' => $pair['bindings'], 'synced_at' => now(), 'last_error' => null]);
        }
        if (integrity() !== loadFile($run.'/integrity-before.json')) throw new RuntimeException('Integrity verification failed; local linking rolled back.');
    });
    if (integrity() !== loadFile($run.'/integrity-before.json')) throw new RuntimeException('Integrity verification failed after linking.');
    saveFile($run.'/linked.json', ['products' => 33, 'variants' => 84, 'integrity_preserved' => true, 'at' => now()->toIso8601String()]);
    echo "Linked 33 existing ERP products and 84 existing ERP variants. Inventory, prices and local relations preserved.\n"; exit;
}

if ($mode === 'verify') {
    $remaining = readProducts(array_column($plan['sources'], 'id'));
    $targets = readProducts(array_merge(array_column($plan['pairs'], 'target_id'), ['gid://shopify/Product/15638176923872']), true);
    $issues = [];
    foreach ($plan['pairs'] as $pair) {
        $local = DB::table('products')->where('store_id', $store->id)->where('id', $pair['erp_id'])->first();
        $remote = $targets[$pair['target_id']] ?? null;
        if (!$local || !$remote || $local->origin !== $pair['target_id'] || $local->name !== $remote['title'] || $remote['status'] !== $pair['status']) $issues[] = $pair['erp_id'];
        foreach ($pair['bindings'] as $id => $binding) if (DB::table('variants')->where('id', $id)->value('shopify_variant_id') !== $binding['variant_id']) $issues[] = $id;
    }
    $before = loadFile($run.'/shopify-before.json'); $stockChanges = [];
    foreach ($plan['pairs'] as $pair) {
        $old = array_column($before[$pair['target_id']]['variants']['nodes'], 'inventoryQuantity', 'id');
        $new = array_column($targets[$pair['target_id']]['variants']['nodes'] ?? [], 'inventoryQuantity', 'id');
        if ($old !== $new) $stockChanges[] = ['product' => $pair['target_id'], 'before' => $old, 'after' => $new];
    }
    $report = ['remaining_originals' => count($remaining), 'manual_products' => count($plan['pairs']), 'manual_variants' => array_sum(array_map(fn ($p) => count($p['bindings']), $plan['pairs'])),
        'binding_issues' => $issues, 'local_integrity_preserved' => integrity() === loadFile($run.'/integrity-before.json'),
        'test_product_preserved' => isset($targets['gid://shopify/Product/15638176923872']), 'manual_stock_changes' => $stockChanges, 'inventory_mutations_executed' => 0];
    saveFile($run.'/verification.json', $report); echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    if ($remaining || $issues || !$report['local_integrity_preserved'] || !$report['test_product_preserved']) exit(1);
    exit;
}
if ($mode === 'hosting-patch') {
    if (!is_file($run.'/linked.json') || !is_file($run.'/verification.json')) throw new RuntimeException('Complete verification first.');
    $report = loadFile($run.'/verification.json');
    if ($report['remaining_originals'] || $report['binding_issues'] || !$report['local_integrity_preserved']) throw new RuntimeException('Verification did not pass.');
    $before = loadFile($run.'/local-before.json');
    $oldProducts = collect($before['products'])->keyBy('id');
    $oldVariants = collect($before['variants'])->keyBy('id');
    $oldExports = collect($before['exports'])->keyBy('product_id');
    $quote = fn ($value) => $value === null ? 'NULL' : DB::connection()->getPdo()->quote((string) $value);
    $checks = ["(SELECT slug FROM stores WHERE id = ".$plan['store_id'].") = 'sorelle'"];
    $updates = [];
    foreach ($plan['pairs'] as $pair) {
        $id = $pair['erp_id']; $old = $oldProducts[$id]; $export = $oldExports[$id];
        $checks[] = '(SELECT COUNT(*) FROM products WHERE id = '.$id.' AND store_id = '.$plan['store_id'].' AND ((origin <=> '.$quote($old['origin']).') OR origin = '.$quote($pair['target_id']).')) = 1';
        $checks[] = '(SELECT COUNT(*) FROM products WHERE store_id = '.$plan['store_id'].' AND id <> '.$id.' AND origin = '.$quote($pair['target_id']).') = 0';
        $checks[] = '(SELECT COUNT(*) FROM shopify_product_exports WHERE id = '.$quote($export['id']).' AND store_id = '.$plan['store_id'].' AND product_id = '.$id.' AND ((remote_product_id <=> '.$quote($export['remote_product_id']).') OR remote_product_id = '.$quote($pair['target_id']).')) = 1';
        $updates[] = '-- ERP #'.$id.' -> '.$pair['name'];
        $updates[] = 'UPDATE products SET name = '.$quote($pair['name']).', origin = '.$quote($pair['target_id']).', online_store_url = '.$quote($pair['online_store_url']).', updated_at = NOW() WHERE id = '.$id.' AND store_id = '.$plan['store_id'].' AND @reconciliation_ok = 1;';
        foreach ($pair['bindings'] as $variantId => $binding) {
            $oldVariant = $oldVariants[$variantId];
            $checks[] = '(SELECT COUNT(*) FROM variants WHERE id = '.$variantId.' AND product_id = '.$id.' AND (shopify_variant_id IS NULL OR shopify_variant_id = '.$quote($oldVariant['shopify_variant_id']).' OR shopify_variant_id = '.$quote($binding['variant_id']).')) = 1';
            $checks[] = '(SELECT COUNT(*) FROM variants WHERE id <> '.$variantId.' AND shopify_variant_id = '.$quote($binding['variant_id']).') = 0';
            $updates[] = 'UPDATE variants SET shopify_variant_id = '.$quote($binding['variant_id']).', updated_at = NOW() WHERE id = '.$variantId.' AND product_id = '.$id.' AND @reconciliation_ok = 1;';
        }
        $updates[] = "UPDATE shopify_product_exports SET status = 'synced', remote_product_id = ".$quote($pair['target_id']).', remote_status = '.$quote($pair['status']).', handle = '.$quote($pair['handle']).', variant_links = '.$quote(json_encode($pair['bindings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)).", synced_at = NOW(), updated_at = NOW(), last_error = NULL WHERE id = ".$quote($export['id']).' AND store_id = '.$plan['store_id'].' AND product_id = '.$id.' AND @reconciliation_ok = 1;';
    }
    $sql = "-- Conciliacion aprobada: 33 productos, 84 variantes. Seleccionar la base del hosting.\n-- Conserva cantidades, precios, pedidos, compras y relaciones del ERP.\n-- No realiza solicitudes ni cambios en Shopify. Respaldar la base antes de aplicar.\n-- Si el resultado dice NO APLICADO, ninguna actualizacion se ejecuta: revisar los vinculos.\nSET NAMES utf8mb4;\nSET @reconciliation_old_sql_mode = @@SQL_MODE;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSTART TRANSACTION;\n";
    $sql .= 'SELECT id FROM products WHERE store_id = '.$plan['store_id'].' AND id IN ('.implode(',', array_column($plan['pairs'], 'erp_id')).") FOR UPDATE;\n";
    $sql .= 'SELECT id FROM variants WHERE product_id IN ('.implode(',', array_column($plan['pairs'], 'erp_id')).") FOR UPDATE;\n";
    $sql .= 'SELECT id FROM shopify_product_exports WHERE store_id = '.$plan['store_id'].' AND product_id IN ('.implode(',', array_column($plan['pairs'], 'erp_id')).") FOR UPDATE;\n";
    $sql .= "SET @reconciliation_ok = IFNULL((\n  ".implode("\n  AND ", $checks)."\n), 0);\n\n";
    $sql .= implode("\n", $updates)."\nCOMMIT;\nSET SQL_MODE = @reconciliation_old_sql_mode;\nSELECT IF(@reconciliation_ok = 1, 'APLICADO: 33 productos y 84 variantes vinculados; stock conservado', 'NO APLICADO: los datos difieren del respaldo esperado; revisar antes de continuar') AS resultado;\n";
    file_put_contents($run.'/actualizar-vinculos-hosting.sql', $sql, LOCK_EX);
    echo json_encode(['hosting_patch' => $run.'/actualizar-vinculos-hosting.sql', 'bytes' => strlen($sql)]).PHP_EOL;
    exit;
}
throw new RuntimeException('Unknown mode.');
