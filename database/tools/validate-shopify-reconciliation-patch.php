<?php
// Rehearses the hosting patch against isolated tables restored from the backup.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$directory = storage_path('app/private/backups/shopify-reconciliation-2026-11-23');
$plan = json_decode(file_get_contents($directory.'/current-plan.json'), true, 512, JSON_THROW_ON_ERROR);
$before = json_decode(file_get_contents($plan['run'].'/local-before.json'), true, 512, JSON_THROW_ON_ERROR);
$config = config('database.connections.'.config('database.default'));
if (!in_array($config['host'], ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Local server required.');
$name = 'erp_shopify_reconcile_test_'.gmdate('Ymd_His');
if (!preg_match('/^erp_shopify_reconcile_test_\d{8}_\d{6}$/D', $name) || $name === $config['database']) throw new RuntimeException('Invalid test database name.');
DB::statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
config(['database.connections.reconciliation_test' => array_merge($config, ['database' => $name])]);
$test = DB::connection('reconciliation_test');
foreach (['stores', 'products', 'variants', 'shopify_product_exports'] as $table) {
    $test->statement('CREATE TABLE `'.$table.'` LIKE `'.str_replace('`', '``', $config['database']).'`.`'.$table.'`');
}
$test->table('stores')->insert((array) DB::table('stores')->where('id', $plan['store_id'])->first());
$test->table('products')->insert($before['products']);
$test->table('variants')->insert($before['variants']);
$test->table('shopify_product_exports')->insert($before['exports']);
$inventoryHash = fn () => hash('sha256', json_encode($test->table('variants')->orderBy('id')->get(['id', 'product_id', 'sku', 'price', 'stock', 'status', 'created_at'])->all(), JSON_THROW_ON_ERROR));
$stockBefore = $inventoryHash();
$runPatch = function (string $suffix) use ($name, $config, $plan): string {
    $output = $plan['run'].'/patch-test-'.$suffix.'.out'; $error = $plan['run'].'/patch-test-'.$suffix.'.err';
    $process = proc_open(['C:/xampp/mysql/bin/mysql.exe', '--no-defaults', '--protocol=tcp', '--host='.$config['host'], '--port='.($config['port'] ?? 3306), '--user='.$config['username'], '--default-character-set=utf8mb4', $name],
        [0 => ['file', $plan['run'].'/actualizar-vinculos-hosting.sql', 'r'], 1 => ['file', $output, 'w'], 2 => ['file', $error, 'w']], $pipes, null, array_merge(getenv(), ['MYSQL_PWD' => (string) ($config['password'] ?? '')]));
    if (!is_resource($process) || proc_close($process) !== 0) throw new RuntimeException('Hosting patch failed in isolated rehearsal; inspect private logs.');
    return file_get_contents($output);
};
$first = $runPatch('first');
$errors = [];
foreach ($plan['pairs'] as $pair) {
    $product = $test->table('products')->where('id', $pair['erp_id'])->first();
    if ($product->origin !== $pair['target_id'] || $product->name !== $pair['name']) $errors[] = $pair['erp_id'];
    foreach ($pair['bindings'] as $id => $binding) if ($test->table('variants')->where('id', $id)->value('shopify_variant_id') !== $binding['variant_id']) $errors[] = $id;
}
$second = $runPatch('repeat');
$inventoryPreserved = $stockBefore === $inventoryHash();
$variant = array_key_first($plan['pairs'][0]['bindings']);
$test->table('variants')->where('id', $variant)->update(['shopify_variant_id' => 'gid://shopify/ProductVariant/999999999999999']);
$snapshot = function () use ($test) { return array_map(fn ($table) => hash('sha256', json_encode($test->table($table)->orderBy('id')->get()->all(), JSON_THROW_ON_ERROR)), ['products', 'variants', 'shopify_product_exports']); };
$guardBefore = $snapshot(); $guardResult = $runPatch('guard'); $guardUnchanged = $guardBefore === $snapshot();
$report = ['isolated_database' => $name, 'first_apply' => str_contains($first, 'APLICADO: 33'), 'repeat_apply' => str_contains($second, 'APLICADO: 33'), 'binding_errors' => $errors,
    'inventory_preserved' => $inventoryPreserved, 'mismatched_data_rejected' => str_contains($guardResult, 'NO APLICADO:'), 'guard_preserves_all_data' => $guardUnchanged];
file_put_contents($plan['run'].'/hosting-patch-validation.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($report, JSON_PRETTY_PRINT).PHP_EOL;
if ($errors || !$report['first_apply'] || !$report['repeat_apply'] || !$inventoryPreserved || !$report['mismatched_data_rejected'] || !$guardUnchanged) exit(1);
