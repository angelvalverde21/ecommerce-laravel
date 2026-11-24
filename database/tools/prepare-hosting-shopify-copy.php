<?php
// Restore the latest hosting download in isolation and apply ONLY the approved bindings.
// Never invokes Shopify and never connects to a remote database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$source = realpath($argv[1] ?? '');
if (!$source || !is_file($source) || strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'sql') throw new RuntimeException('Provide the downloaded SQL file.');
$originalHash = hash_file('sha256', $source);
$original = file_get_contents($source);
if (preg_match('/^\s*(?:USE\s|(?:CREATE|DROP)\s+DATABASE\s|DELIMITER\s)/mi', $original)) throw new RuntimeException('The dump changes database context or defines routines; review before importing.');

$plan = json_decode(file_get_contents(storage_path('app/private/backups/shopify-reconciliation-2026-11-23/current-plan.json')), true, 512, JSON_THROW_ON_ERROR);
$patch = $plan['run'].'/actualizar-vinculos-hosting.sql';
if (!is_file($patch) || count($plan['pairs']) !== 33) throw new RuntimeException('The approved reconciliation patch is required.');
$config = config('database.connections.'.config('database.default'));
if (!in_array($config['host'], ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Local database server required.');
$stamp = gmdate('Ymd_His');
$database = 'erp_hosting_shopify_'.$stamp;
if (!preg_match('/^erp_hosting_shopify_\d{8}_\d{6}$/D', $database) || $database === $config['database']) throw new RuntimeException('Invalid isolated database.');
$directory = storage_path('app/private/backups/hosting-shopify-'.$stamp);
if (!mkdir($directory, 0700, true)) throw new RuntimeException('Could not create the private output directory.');
$report = ['source' => $source, 'source_sha256' => $originalHash, 'isolated_database' => $database, 'shopify_requests' => 0];

function saveReport(): void {
    global $report, $directory;
    file_put_contents($directory.'/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}
function runSql(string $file, string $label, ?string $targetDatabase = null): string {
    global $config, $database, $directory;
    $output = $directory.'/'.$label.'.out'; $error = $directory.'/'.$label.'.err';
    $args = ['C:/xampp/mysql/bin/mysql.exe', '--no-defaults', '--protocol=tcp', '--host='.$config['host'], '--port='.($config['port'] ?? 3306), '--user='.$config['username'], '--default-character-set=utf8mb4', $targetDatabase ?? $database];
    $process = proc_open($args, [0 => ['file', $file, 'r'], 1 => ['file', $output, 'w'], 2 => ['file', $error, 'w']], $pipes, null, array_merge(getenv(), ['MYSQL_PWD' => (string) ($config['password'] ?? '')]));
    if (!is_resource($process) || proc_close($process) !== 0) throw new RuntimeException('SQL operation failed: '.$label.'. Inspect the private error log.');
    return file_get_contents($output);
}
function preservedData($connection): array {
    $except = [
        'products' => ['name', 'origin', 'online_store_url', 'updated_at'],
        'variants' => ['shopify_variant_id', 'updated_at'],
        'shopify_product_exports' => ['status', 'remote_product_id', 'remote_status', 'handle', 'variant_links', 'synced_at', 'updated_at', 'last_error'],
    ];
    $result = [];
    foreach ($connection->select('SHOW TABLES') as $row) {
        $table = array_values((array) $row)[0];
        $columns = array_values(array_diff($connection->getSchemaBuilder()->getColumnListing($table), $except[$table] ?? []));
        $rows = $connection->table($table)->get($columns)->map(fn ($r) => json_encode($r, JSON_THROW_ON_ERROR))->sort()->values()->all();
        $result[$table] = ['rows' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }
    ksort($result);
    return $result;
}
function checkBindings($connection): array {
    global $plan;
    $errors = []; $variantCount = 0;
    foreach ($plan['pairs'] as $pair) {
        $product = $connection->table('products')->where('store_id', $plan['store_id'])->where('id', $pair['erp_id'])->first();
        if (!$product || $product->origin !== $pair['target_id'] || $product->name !== $pair['name']) $errors[] = 'product:'.$pair['erp_id'];
        $export = $connection->table('shopify_product_exports')->where('store_id', $plan['store_id'])->where('product_id', $pair['erp_id'])->first();
        if (!$export || $export->remote_product_id !== $pair['target_id'] || $export->status !== 'synced') $errors[] = 'export:'.$pair['erp_id'];
        $links = $export ? json_decode($export->variant_links ?? '{}', true) : [];
        foreach ($pair['bindings'] as $id => $binding) {
            $variantCount++;
            $variant = $connection->table('variants')->where('id', $id)->where('product_id', $pair['erp_id'])->first();
            if (!$variant || $variant->shopify_variant_id !== $binding['variant_id'] || ($links[$id]['variant_id'] ?? null) !== $binding['variant_id']) $errors[] = 'variant:'.$id;
        }
    }
    return ['products' => count($plan['pairs']), 'variants' => $variantCount, 'errors' => $errors];
}

try {
    DB::statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    config(['database.connections.hosting_shopify_copy' => array_merge($config, ['database' => $database])]);
    $connection = DB::connection('hosting_shopify_copy');
    runSql($source, 'restore-original');
    $before = preservedData($connection);
    $report['before_bindings'] = checkBindings($connection);
    $first = runSql($patch, 'apply-approved-patch');
    if (!str_contains($first, 'APLICADO: 33') || str_contains($first, 'NO APLICADO:')) throw new RuntimeException('The approved patch rejected this backup; no updated artifact will be generated.');
    $report['bindings'] = checkBindings($connection);
    $after = preservedData($connection);
    $report['preserved_tables'] = $after;
    $report['unrelated_data_preserved'] = $before === $after;
    if (!$report['unrelated_data_preserved'] || $report['bindings']['errors'] || $report['bindings']['variants'] !== 84) throw new RuntimeException('Data verification failed.');

    // Check repeat imports cannot create extra products or variants.
    $repeat = runSql($patch, 'repeat-approved-patch');
    $report['repeat_apply_verified'] = str_contains($repeat, 'APLICADO: 33') && !str_contains($repeat, 'NO APLICADO:') && preservedData($connection) === $after && !checkBindings($connection)['errors'];
    if (!$report['repeat_apply_verified']) throw new RuntimeException('Repeat-apply verification failed.');

    // Export the updated rows, then restore that exact artifact to prove it is usable.
    $full = $directory.'/tecnoplanet_sorelle_con_vinculos_shopify.sql';
    $args = ['C:/xampp/mysql/bin/mysqldump.exe', '--no-defaults', '--protocol=tcp', '--host='.$config['host'], '--port='.($config['port'] ?? 3306), '--user='.$config['username'], '--default-character-set=utf8mb4', '--single-transaction', '--skip-lock-tables', '--skip-add-drop-table', '--skip-comments', '--hex-blob', $database];
    $process = proc_open($args, [0 => ['pipe', 'r'], 1 => ['file', $full, 'w'], 2 => ['file', $directory.'/export.err', 'w']], $pipes, null, array_merge(getenv(), ['MYSQL_PWD' => (string) ($config['password'] ?? '')]));
    if (!is_resource($process)) throw new RuntimeException('Could not start the database export.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0 || !filesize($full)) throw new RuntimeException('The updated database export failed.');
    $verificationDatabase = $database.'_verify';
    DB::statement('CREATE DATABASE `'.$verificationDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    config(['database.connections.hosting_shopify_verify' => array_merge($config, ['database' => $verificationDatabase])]);
    runSql($full, 'restore-final-copy', $verificationDatabase);
    $verification = DB::connection('hosting_shopify_verify');
    $report['full_backup_restore_verified'] = preservedData($verification) === $after && !checkBindings($verification)['errors'];
    if (!$report['full_backup_restore_verified']) throw new RuntimeException('The final SQL artifact failed its restore verification.');
    copy($patch, $directory.'/solo_actualizar_vinculos_shopify.sql');
    $report['source_unchanged'] = hash_file('sha256', $source) === $originalHash;
    if (!$report['source_unchanged']) throw new RuntimeException('The source backup changed during preparation.');
    $report['artifacts'] = ['full_backup' => $full, 'patch' => $directory.'/solo_actualizar_vinculos_shopify.sql'];
    $report['full_backup_bytes'] = filesize($full);
    $report['full_backup_sha256'] = hash_file('sha256', $full);
    $report['success'] = true;
    saveReport();
    echo json_encode(['success' => true, 'directory' => $directory, 'database' => $database, 'bindings' => $report['bindings'], 'preserved_tables' => count($after), 'source_unchanged' => true, 'unrelated_data_preserved' => true, 'repeat_apply_verified' => true, 'full_backup_restore_verified' => true, 'artifacts' => $report['artifacts']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
} catch (Throwable $error) {
    $report['success'] = false; $report['error'] = $error->getMessage(); saveReport();
    fwrite(STDERR, $error->getMessage().PHP_EOL.'Private report: '.$directory.'/verification.json'.PHP_EOL);
    exit(1);
}
