<?php
// Read content from explicitly linked Shopify products; apply only to the local ERP.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Services\Shopify\Catalog\CatalogClient;
use Illuminate\Support\Facades\DB;

$mode = $argv[1] ?? 'prepare';
$config = config('database.connections.'.config('database.default'));
if (!in_array($config['host'], ['localhost', '127.0.0.1', '::1'], true) || $config['database'] !== 'sorelle_production') throw new RuntimeException('Local sorelle_production required.');
$store = DB::table('stores')->where('slug', 'sorelle')->first();
$client = app(CatalogClient::class);
if (!$store || $client->domain() !== 'sorelleperu.myshopify.com') throw new RuntimeException('Unexpected store.');
$morph = (new Product)->getMorphClass();
function saveJson(string $path, mixed $value): void { file_put_contents($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX); }
function fingerprint(): array {
    $result = [];
    foreach (DB::select('SHOW TABLES') as $row) {
        $table = array_values((array) $row)[0];
        if ($table === 'images') continue;
        $columns = DB::connection()->getSchemaBuilder()->getColumnListing($table);
        if ($table === 'products') $columns = array_values(array_diff($columns, ['body', 'updated_at']));
        $rows = DB::table($table)->get($columns)->map(fn ($r) => json_encode($r, JSON_THROW_ON_ERROR))->sort()->values()->all();
        $result[$table] = ['count' => count($rows), 'hash' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }
    ksort($result); return $result;
}
function safeImage(array $image): bool {
    $p = parse_url($image['url'] ?? ''); $host = strtolower($p['host'] ?? '');
    return ($p['scheme'] ?? '') === 'https' && !isset($p['user']) && !isset($p['pass']) && !isset($p['port'])
        && ($host === 'cdn.shopify.com' || str_ends_with($host, '.shopifycdn.com'));
}
if ($mode === 'prepare') {
    $linked = DB::table('shopify_product_exports as e')->join('products as p', 'p.id', '=', 'e.product_id')
        ->where('e.store_id', $store->id)->where('p.store_id', $store->id)->where('e.shop_domain', $client->domain())->where('e.status', 'synced')
        ->orderBy('p.id')->get(['p.id', 'p.name', 'p.origin', 'p.body', 'p.updated_at', 'e.remote_product_id']);
    if (!$linked->count() || $linked->contains(fn ($p) => $p->origin !== $p->remote_product_id)) throw new RuntimeException('The persisted product associations must match.');
    $remote = [];
    foreach (array_chunk($linked->pluck('remote_product_id')->all(), 5) as $ids) {
        $data = $client->request('query LinkedContent($ids:[ID!]!) { nodes(ids:$ids) { ... on Product { id title descriptionHtml images(first:100) { nodes { id url altText } pageInfo { hasNextPage endCursor } } } } }', ['ids' => $ids]);
        foreach ($data['nodes'] as $node) {
            if (!$node) throw new RuntimeException('A linked product is missing in Shopify.');
            $images = $node['images']['nodes']; $page = $node['images']['pageInfo'];
            while ($page['hasNextPage']) {
                $more = $client->request('query LinkedContentImages($id:ID!,$after:String!) { product(id:$id) { images(first:100,after:$after) { nodes { id url altText } pageInfo { hasNextPage endCursor } } } }', ['id' => $node['id'], 'after' => $page['endCursor']]);
                $images = array_merge($images, $more['product']['images']['nodes']); $page = $more['product']['images']['pageInfo'];
            }
            foreach ($images as $image) if (!safeImage($image)) throw new RuntimeException('Unexpected image host.');
            $remote[$node['id']] = ['id' => $node['id'], 'title' => $node['title'], 'html' => $node['descriptionHtml'], 'images' => $images];
        }
    }
    $directory = storage_path('app/private/backups/shopify-content-'.gmdate('Ymd_His'));
    if (!mkdir($directory, 0700, true)) throw new RuntimeException('Could not create backup directory.');
    $products = DB::table('products')->whereIn('id', $linked->pluck('id'))->orderBy('id')->get();
    $images = DB::table('images')->where('imageable_type', $morph)->whereIn('imageable_id', $linked->pluck('id'))->orderBy('id')->get();
    saveJson($directory.'/before.json', ['products' => $products, 'images' => $images]);
    saveJson($directory.'/integrity-before.json', fingerprint());
    saveJson($directory.'/plan.json', ['database' => $config['database'], 'store_id' => $store->id, 'morph' => $morph, 'linked' => $linked, 'remote' => $remote]);
    $summary = ['directory' => $directory, 'products' => $linked->count(), 'nonempty_descriptions' => collect($remote)->filter(fn ($p) => trim($p['html']) !== '')->count(),
        'shopify_images' => collect($remote)->sum(fn ($p) => count($p['images'])), 'existing_erp_images' => $images->count(), 'database_changes' => 0];
    saveJson($directory.'/summary.json', $summary); echo json_encode($summary, JSON_PRETTY_PRINT).PHP_EOL; exit;
}
if ($mode !== 'apply' || !in_array('--apply-approved', $argv, true)) throw new RuntimeException('Use prepare, or apply with the reviewed plan directory and --apply-approved.');
$directory = realpath($argv[2] ?? '');
$root = realpath(storage_path('app/private/backups'));
if (!$directory || !str_starts_with(strtolower($directory), strtolower($root).DIRECTORY_SEPARATOR) || !str_starts_with(basename($directory), 'shopify-content-')) throw new RuntimeException('Invalid content backup directory.');
$plan = json_decode(file_get_contents($directory.'/plan.json'), true, 512, JSON_THROW_ON_ERROR);
$before = json_decode(file_get_contents($directory.'/before.json'), true, 512, JSON_THROW_ON_ERROR);
$integrity = json_decode(file_get_contents($directory.'/integrity-before.json'), true, 512, JSON_THROW_ON_ERROR);
if ($plan['database'] !== $config['database'] || $plan['store_id'] !== $store->id || fingerprint() !== $integrity) throw new RuntimeException('Database changed since preparation; prepare a new snapshot.');
$result = DB::transaction(function () use ($plan, $before, $integrity, $morph) {
    $updated = 0; $inserted = 0; $addedIds = [];
    foreach ($plan['linked'] as $linked) {
        $product = DB::table('products')->where('store_id', $plan['store_id'])->where('id', $linked['id'])->lockForUpdate()->first();
        if (!$product || $product->origin !== $linked['remote_product_id'] || $product->body !== $linked['body'] || (string) $product->updated_at !== (string) $linked['updated_at']) throw new RuntimeException('A product changed since preparation.');
        $remote = $plan['remote'][$linked['remote_product_id']];
        if (trim($remote['html']) !== '' && $product->body !== $remote['html']) {
            DB::table('products')->where('id', $product->id)->update(['body' => $remote['html'], 'updated_at' => now()]); $updated++;
        }
        foreach ($remote['images'] as $image) {
            $url = $image['url'];
            if (DB::table('images')->where('imageable_type', $morph)->where('imageable_id', $product->id)->where(fn ($q) => $q->where('name', $url)->orWhere('large', $url))->exists()) continue;
            $addedIds[] = DB::table('images')->insertGetId(['imageable_type' => $morph, 'imageable_id' => $product->id, 'name' => $url,
                'title' => mb_substr($image['altText'] ?: $remote['title'], 0, 255), 'thumbnail' => shopify_image_by_height($url, 300), 'medium' => shopify_image_by_height($url, 800), 'large' => $url, 'created_at' => now(), 'updated_at' => now()]);
            $inserted++;
        }
    }
    foreach ($before['images'] as $image) if ((array) DB::table('images')->where('id', $image['id'])->first() !== $image) throw new RuntimeException('An existing image changed.');
    if (fingerprint() !== $integrity) throw new RuntimeException('Unrelated data changed; rolling back.');
    foreach ($plan['linked'] as $linked) {
        $remote = $plan['remote'][$linked['remote_product_id']];
        if (trim($remote['html']) !== '' && DB::table('products')->where('id', $linked['id'])->value('body') !== $remote['html']) throw new RuntimeException('Description verification failed.');
        foreach ($remote['images'] as $image) if (!DB::table('images')->where('imageable_type', $morph)->where('imageable_id', $linked['id'])->where('large', $image['url'])->exists()) throw new RuntimeException('Image verification failed.');
    }
    return ['products' => count($plan['linked']), 'descriptions_updated' => $updated, 'images_added' => $inserted, 'new_image_ids' => $addedIds, 'inventory_and_links_preserved' => true, 'existing_images_preserved' => true, 'shopify_mutations' => 0];
});
saveJson($directory.'/applied.json', $result);
echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL;
