<?php
// Enrich a hosting SQL backup with the already imported Shopify descriptions/photos.
// Every SQL write targets an isolated local database; never imports the working ERP.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$source = realpath($argv[1] ?? '');
$contentDirectory = realpath($argv[2] ?? '');
if (!$source || !is_file($source) || !$contentDirectory || !is_file($contentDirectory.'/applied.json')) throw new RuntimeException('Provide the hosting SQL and the completed content-import backup directory.');
$sourceHash = hash_file('sha256', $source);
if (preg_match('/^\s*(?:USE\s|(?:CREATE|DROP)\s+DATABASE\s|DELIMITER\s)/mi', file_get_contents($source))) throw new RuntimeException('Unexpected database context in source SQL.');
$plan = json_decode(file_get_contents($contentDirectory.'/plan.json'), true, 512, JSON_THROW_ON_ERROR);
if (count($plan['linked']) !== 33 || $plan['store_id'] !== 1) throw new RuntimeException('Unexpected content plan.');
$config = config('database.connections.'.config('database.default'));
if (!in_array($config['host'], ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Local server required.');
$database = 'erp_hosting_content_'.gmdate('Ymd_His');
$directory = storage_path('app/private/backups/hosting-content-'.gmdate('Ymd_His'));
if (!mkdir($directory, 0700, true)) throw new RuntimeException('Cannot create private backup directory.');
$env = array_merge(getenv(), ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
$connectionArgs = ['--no-defaults', '--protocol=tcp', '--host='.$config['host'], '--port='.($config['port'] ?? 3306), '--user='.$config['username'], '--default-character-set=utf8mb4'];
function runSql(string $file, string $db, string $label): string {
    global $connectionArgs, $env, $directory;
    $p = proc_open(array_merge(['C:/xampp/mysql/bin/mysql.exe'], $connectionArgs, [$db]), [0 => ['file', $file, 'r'], 1 => ['file', $directory.'/'.$label.'.out', 'w'], 2 => ['file', $directory.'/'.$label.'.err', 'w']], $pipes, null, $env);
    if (!is_resource($p) || proc_close($p) !== 0) throw new RuntimeException('SQL failed: '.$label.'; inspect the private log.');
    return file_get_contents($directory.'/'.$label.'.out');
}
function integrity($db): array {
    $out = [];
    foreach ($db->select('SHOW TABLES') as $row) {
        $table = array_values((array) $row)[0];
        if ($table === 'images') continue;
        $cols = $db->getSchemaBuilder()->getColumnListing($table);
        if ($table === 'products') $cols = array_values(array_diff($cols, ['body', 'updated_at']));
        $rows = $db->table($table)->get($cols)->map(fn ($r) => json_encode($r, JSON_THROW_ON_ERROR))->sort()->values()->all();
        $out[$table] = ['rows' => count($rows), 'hash' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }
    ksort($out); return $out;
}
function verifyContent($db): array {
    global $plan;
    $errors = []; $images = 0;
    foreach ($plan['linked'] as $p) {
        $remote = $plan['remote'][$p['remote_product_id']];
        $local = $db->table('products')->where('store_id', 1)->where('id', $p['id'])->first();
        if (!$local || $local->origin !== $p['remote_product_id'] || $local->body !== $remote['html']) $errors[] = 'product:'.$p['id'];
        foreach ($remote['images'] as $im) {
            $images++;
            if ($db->table('images')->where('imageable_type', $plan['morph'])->where('imageable_id', $p['id'])->where('large', $im['url'])->count() !== 1) $errors[] = 'image:'.$im['id'];
        }
    }
    return ['products' => count($plan['linked']), 'images' => $images, 'errors' => $errors];
}

DB::statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
config(['database.connections.hosting_content' => array_merge($config, ['database' => $database])]);
$db = DB::connection('hosting_content');
runSql($source, $database, 'restore-original');
$before = integrity($db);
$beforeImages = $db->table('images')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
$quote = fn ($v) => $v === null ? 'NULL' : $db->getPdo()->quote((string) $v);
$checks = ["(SELECT slug FROM stores WHERE id = 1) = 'sorelle'"];
$updates = [];
foreach ($plan['linked'] as $p) {
    $remote = $plan['remote'][$p['remote_product_id']];
    $product = $db->table('products')->where('store_id', 1)->where('id', $p['id'])->first();
    if (!$product || $product->origin !== $p['remote_product_id']) throw new RuntimeException('The hosting backup does not have the approved Shopify bindings for product '.$p['id']);
    $checks[] = '(SELECT COUNT(*) FROM products WHERE store_id = 1 AND id = '.$p['id'].' AND origin = '.$quote($p['remote_product_id']).' AND ((body <=> '.$quote($product->body).') OR (body <=> '.$quote($remote['html']).'))) = 1';
    $updates[] = 'UPDATE products SET body = '.$quote($remote['html']).', updated_at = NOW() WHERE store_id = 1 AND id = '.$p['id'].' AND @shopify_content_ok = 1 AND NOT(body <=> '.$quote($remote['html']).');';
    foreach ($remote['images'] as $image) {
        $values = ['name' => $image['url'], 'title' => mb_substr($image['altText'] ?: $remote['title'], 0, 255),
            'thumbnail' => shopify_image_by_height($image['url'], 300), 'medium' => shopify_image_by_height($image['url'], 800), 'large' => $image['url'], 'imageable_type' => $plan['morph'], 'imageable_id' => $p['id']];
        $updates[] = 'INSERT INTO images ('.implode(',', array_keys($values)).',created_at,updated_at) SELECT '.implode(',', array_map($quote, array_values($values))).',NOW(),NOW() FROM DUAL WHERE @shopify_content_ok = 1 AND NOT EXISTS (SELECT 1 FROM images WHERE imageable_type = '.$quote($plan['morph']).' AND imageable_id = '.$p['id'].' AND (name = '.$quote($image['url']).' OR large = '.$quote($image['url']).'));';
    }
}
$patch = $directory.'/solo_fotos_y_descripciones_shopify.sql';
$sql = "-- 33 descriptions and 161 Shopify-CDN images. No stock or price updates.\n-- Requires the approved Shopify product bindings. Safe to repeat.\nSET NAMES utf8mb4;\nSET @shopify_content_previous_sql_mode = @@SQL_MODE;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSTART TRANSACTION;\n";
$sql .= 'SELECT id FROM products WHERE store_id = 1 AND id IN ('.implode(',', array_column($plan['linked'], 'id')).") FOR UPDATE;\n";
$sql .= 'SET @shopify_content_ok = IFNULL(('.implode("\n AND ", $checks)."), 0);\n".implode("\n", $updates)."\nCOMMIT;\nSET SQL_MODE = @shopify_content_previous_sql_mode;\nSELECT IF(@shopify_content_ok = 1, 'APLICADO: fotos y descripciones de 33 productos; stock conservado', 'NO APLICADO: revisar los vinculos o las descripciones existentes') AS resultado;\n";
file_put_contents($patch, $sql, LOCK_EX);
$first = runSql($patch, $database, 'apply-content');
if (str_contains($first, 'NO APLICADO:') || !str_contains($first, 'APLICADO: fotos')) throw new RuntimeException('Content patch rejected.');
$content = verifyContent($db);
if ($content['errors'] || integrity($db) !== $before) throw new RuntimeException('Content or unrelated data verification failed.');
foreach ($beforeImages as $image) if ((array) $db->table('images')->where('id', $image['id'])->first() !== $image) throw new RuntimeException('An existing image changed.');
$imageCount = $db->table('images')->count();
runSql($patch, $database, 'repeat-content');
if ($db->table('images')->count() !== $imageCount || verifyContent($db)['errors']) throw new RuntimeException('Repeat application produced differences.');
$full = $directory.'/'.basename($source);
$p = proc_open(array_merge(['C:/xampp/mysql/bin/mysqldump.exe'], $connectionArgs, ['--single-transaction', '--skip-lock-tables', '--skip-add-drop-table', '--skip-comments', '--hex-blob', $database]), [0 => ['pipe', 'r'], 1 => ['file', $full, 'w'], 2 => ['file', $directory.'/export.err', 'w']], $pipes, null, $env);
if (!is_resource($p)) throw new RuntimeException('Cannot export updated backup.');
fclose($pipes[0]); if (proc_close($p) !== 0) throw new RuntimeException('Export failed.');
$verifyDatabase = $database.'_verify';
DB::statement('CREATE DATABASE `'.$verifyDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
config(['database.connections.hosting_content_verify' => array_merge($config, ['database' => $verifyDatabase])]);
runSql($full, $verifyDatabase, 'restore-export');
$verify = DB::connection('hosting_content_verify');
if (integrity($verify) !== $before || verifyContent($verify)['errors'] || $verify->table('images')->count() !== $imageCount) throw new RuntimeException('Final SQL restore verification failed.');
if (hash_file('sha256', $source) !== $sourceHash) throw new RuntimeException('Source changed during preparation.');
$report = ['source' => $source, 'source_sha256' => $sourceHash, 'database' => $database, 'content' => $content, 'images_before' => count($beforeImages), 'images_after' => $imageCount,
    'unrelated_data_preserved' => true, 'existing_images_preserved' => true, 'repeat_apply_verified' => true, 'full_restore_verified' => true, 'shopify_requests' => 0,
    'full_backup' => $full, 'content_patch' => $patch, 'full_sha256' => hash_file('sha256', $full)];
file_put_contents($directory.'/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
