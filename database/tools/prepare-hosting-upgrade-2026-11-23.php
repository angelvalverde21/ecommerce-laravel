<?php

// Rehearses the hosted backup in isolated local databases; never changes the configured database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
$dataDirectory = $root.'/database/data';
$planPath = $dataDirectory.'/.hosting-upgrade-plan-2026-11-23.json';
$mode = $argv[1] ?? 'inspect';
$originalConnection = config('database.default');
$configuration = config('database.connections.'.$originalConnection);
if (!in_array($configuration['host'] ?? '', ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('This utility only runs against a local database server.');
}
$source = $dataDirectory.'/tecnoplanet_sorelle_23_11_2026.sql';
$sourceHash = hash_file('sha256', $source);

function saveJson(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

function isolatedConnection(string $name): void
{
    global $configuration;
    if (!preg_match('/^erp_hosting_review_[a-z0-9_]+$/D', $name) || $name === $configuration['database']) {
        throw new RuntimeException('Invalid isolated database name.');
    }
    config(['database.connections.hosting_upgrade' => array_merge($configuration, ['database' => $name])]);
    DB::purge('hosting_upgrade');
    DB::setDefaultConnection('hosting_upgrade');
}

function createIsolatedDatabase(string $name): void
{
    global $originalConnection;
    if (!preg_match('/^erp_hosting_review_[a-z0-9_]+$/D', $name)) throw new RuntimeException('Invalid database name.');
    DB::connection($originalConnection)->statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}

function runClient(string $program, string $database, ?string $input, string $output, string $error, array $options = []): void
{
    global $configuration;
    if (!preg_match('/^erp_hosting_review_[a-z0-9_]+$/D', $database)) throw new RuntimeException('Client target must be isolated.');
    $command = array_merge(['C:/xampp/mysql/bin/'.$program.'.exe', '--no-defaults', '--protocol=tcp',
        '--host='.$configuration['host'], '--port='.($configuration['port'] ?? 3306),
        '--user='.$configuration['username'], '--default-character-set=utf8mb4'], $options, [$database]);
    $descriptors = [0 => $input ? ['file', $input, 'r'] : ['pipe', 'r'], 1 => ['file', $output, 'w'], 2 => ['file', $error, 'w']];
    $process = proc_open($command, $descriptors, $pipes, null, array_merge(getenv(), ['MYSQL_PWD' => (string) ($configuration['password'] ?? '')]));
    if (!is_resource($process)) throw new RuntimeException('Could not start SQL client.');
    if (!$input) fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('SQL client failed; inspect the private error log.');
}

function tableNames(): array
{
    return array_map(fn ($row) => $row->table_name ?? $row->TABLE_NAME, DB::select(
        'SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = ? ORDER BY table_name',
        [DB::connection()->getDatabaseName(), 'BASE TABLE']));
}

function tableColumns(string $table): array
{
    return array_map(fn ($row) => $row->column_name ?? $row->COLUMN_NAME, DB::select(
        'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position',
        [DB::connection()->getDatabaseName(), $table]));
}

function fingerprint(string $table, array $columns, ?array $migrationIds = null): array
{
    $query = DB::table($table)->select($columns);
    if ($table === 'migrations' && $migrationIds !== null) $query->whereIn('id', $migrationIds);
    $keys = DB::select('SHOW KEYS FROM `'.str_replace('`', '``', $table).'` WHERE Key_name = ?', ['PRIMARY']);
    foreach ($keys ?: array_map(fn ($name) => (object) ['Column_name' => $name], $columns) as $key) $query->orderBy($key->Column_name);
    $rows = $query->get()->map(fn ($row) => (array) $row)->all();
    return ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR))];
}

function databaseSnapshot(): array
{
    $result = [];
    foreach (tableNames() as $table) {
        $columns = tableColumns($table);
        $create = (array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
        $ddl = array_values($create)[1];
        $result[$table] = ['columns' => $columns, 'data' => fingerprint($table, $columns),
            'ddl' => preg_replace('/ AUTO_INCREMENT=\d+/', '', $ddl)];
    }
    return $result;
}

if ($mode === 'capture-local') {
    $schema = [];
    foreach (tableNames() as $table) {
        $create = (array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
        $schema[$table] = array_values($create)[1];
    }
    saveJson($dataDirectory.'/.hosting-local-schema-2026-11-23.json', [
        'database' => $configuration['database'], 'schema' => $schema,
        'migrations' => DB::table('migrations')->orderBy('migration')->pluck('migration')->all(),
    ]);
    echo json_encode(['local_tables' => count($schema), 'migrations' => DB::table('migrations')->count()]).PHP_EOL;
    exit;
}

if ($mode === 'init') {
    if (is_file($planPath)) throw new RuntimeException('A preparation plan already exists.');
    $sql = file_get_contents($source);
    if (preg_match('/^\s*(USE\s|CREATE\s+DATABASE|DROP\s+DATABASE|DELIMITER|CREATE\s+(?:TRIGGER|PROCEDURE|EVENT))/mi', $sql)) {
        throw new RuntimeException('The source contains database-switching or executable objects that require review.');
    }
    $suffix = '20261123_'.bin2hex(random_bytes(4));
    $work = $dataDirectory.'/.hosting-upgrade-'.$suffix;
    mkdir($work, 0700, true);
    $plan = ['source_sha256' => $sourceHash, 'work' => $work, 'database' => 'erp_hosting_review_'.$suffix,
        'patch_database' => 'erp_hosting_review_'.$suffix.'_patch', 'dump_database' => 'erp_hosting_review_'.$suffix.'_dump',
        'full_sql' => $dataDirectory.'/tecnoplanet_sorelle_actualizada_2026-11-23.sql',
        'patch_sql' => $dataDirectory.'/tecnoplanet_sorelle_actualizacion_2026-11-23.sql'];
    createIsolatedDatabase($plan['database']);
    runClient('mysql', $plan['database'], $source, $work.'/import.out', $work.'/import.err', ['--binary-mode=1']);
    isolatedConnection($plan['database']);
    $baseline = databaseSnapshot();
    saveJson($work.'/baseline.json', $baseline);
    $plan['baseline_migration_ids'] = DB::table('migrations')->pluck('id')->all();
    $local = json_decode(file_get_contents($dataDirectory.'/.hosting-local-schema-2026-11-23.json'), true, 512, JSON_THROW_ON_ERROR);
    $host = DB::table('migrations')->pluck('migration')->all();
    $plan['migrations'] = array_values(array_diff($local['migrations'], $host));
    saveJson($planPath, $plan);
    echo json_encode(['status' => 'imported', 'tables' => count($baseline), 'target_migrations' => count($plan['migrations'])]).PHP_EOL;
    exit;
}

if (!is_file($planPath)) throw new RuntimeException('Run init first.');
$plan = json_decode(file_get_contents($planPath), true, 512, JSON_THROW_ON_ERROR);
if ($plan['source_sha256'] !== $sourceHash) throw new RuntimeException('The original source has changed.');
$work = $plan['work'];
isolatedConnection($plan['database']);
$app->useStoragePath($work.'/storage');

if ($mode === 'migrate') {
    if (is_file($plan['patch_sql'])) throw new RuntimeException('Patch output already exists; do not append another run.');
    // Bindings for TIMESTAMP values are wall-clock strings in this connection's timezone.
    // Replaying them as UTC would shift the newly created records by the server offset.
    $offset = (int) DB::selectOne('SELECT TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) AS seconds')->seconds;
    $timezone = sprintf('%s%02d:%02d', $offset < 0 ? '-' : '+', intdiv(abs($offset), 3600), intdiv(abs($offset) % 3600, 60));
    file_put_contents($plan['patch_sql'], '-- Upgrade from hosted backup SHA256 '.$sourceHash."\n-- Apply once to the matching backup/schema; rehearse and back up before use.\nSET NAMES utf8mb4;\nSET time_zone = '".$timezone."';\n\n");
    DB::listen(function ($query) use ($plan) {
        if ($query->connection->getDatabaseName() !== $plan['database']) return;
        if (!preg_match('/^\s*(CREATE|ALTER|RENAME|DROP|INSERT|UPDATE|DELETE|REPLACE|SET)\b/i', $query->sql)) return;
        $raw = $query->connection->getQueryGrammar()->substituteBindingsIntoRawSql($query->sql, $query->connection->prepareBindings($query->bindings));
        if (str_contains($raw, $plan['database'])) throw new RuntimeException('A database-specific statement cannot be included in the patch.');
        file_put_contents($plan['patch_sql'], rtrim($raw, ';').";\n", FILE_APPEND);
    });
    foreach ($plan['migrations'] as $migration) {
        file_put_contents($plan['patch_sql'], "\n-- ".$migration."\n", FILE_APPEND);
        $exit = Artisan::call('migrate', ['--database' => 'hosting_upgrade', '--path' => ['database/migrations/'.$migration.'.php'], '--force' => true]);
        file_put_contents($work.'/migrations.log', Artisan::output(), FILE_APPEND);
        if ($exit !== 0) throw new RuntimeException('Migration failed: '.$migration);
        echo 'Applied '.$migration.PHP_EOL;
    }
    saveJson($work.'/upgraded.json', databaseSnapshot());
    echo "Migrations completed.\n";
    exit;
}

if ($mode === 'audit') {
    $baseline = json_decode(file_get_contents($work.'/baseline.json'), true, 512, JSON_THROW_ON_ERROR);
    $checks = [];
    foreach ($baseline as $table => $state) {
        $columns = $state['columns'];
        $expected = $state['data'];
        $actual = fingerprint($table, $columns, $table === 'migrations' ? $plan['baseline_migration_ids'] : null);
        $checks[$table] = ['before' => $expected['count'], 'after_original_records' => $actual['count'], 'preserved' => $actual === $expected];
    }
    $local = json_decode(file_get_contents($dataDirectory.'/.hosting-local-schema-2026-11-23.json'), true, 512, JSON_THROW_ON_ERROR);
    $missingTables = array_values(array_diff(array_keys($local['schema']), tableNames()));
    $columnDifferences = [];
    $definitionDifferences = [];
    $indexNameDifferences = [];
    foreach ($local['schema'] as $table => $ddl) {
        if (!in_array($table, tableNames(), true)) continue;
        preg_match_all('/^  `([^`]+)`/m', $ddl, $matches);
        $actual = tableColumns($table);
        if ($missing = array_values(array_diff($matches[1], $actual))) $columnDifferences[$table] = ['missing_columns' => $missing];
        $create = array_values((array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`'))[1];
        $localDefinition = preg_replace('/ AUTO_INCREMENT=\d+/', '', $ddl);
        $hostDefinition = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create);
        if ($localDefinition !== $hostDefinition) {
            if (preg_replace('/(KEY )`[^`]+`/', '$1`index_name`', $localDefinition) === preg_replace('/(KEY )`[^`]+`/', '$1`index_name`', $hostDefinition)) {
                $indexNameDifferences[] = $table;
            } else $definitionDifferences[] = $table;
        }
    }
    $report = ['source_sha256' => $sourceHash, 'migrations_applied' => $plan['migrations'], 'original_tables' => count($baseline),
        'updated_tables' => count(tableNames()), 'preservation' => $checks, 'missing_local_tables' => $missingTables,
        'missing_local_columns' => $columnDifferences, 'different_local_definitions' => $definitionDifferences,
        'equivalent_indexes_with_different_names' => $indexNameDifferences,
        'production_stages' => DB::table('production_stages')->count(),
        'productions' => DB::table('manufactures')->where('type', 'production')->count(), 'progress_rows' => DB::table('production_stage_progress')->count()];
    saveJson($work.'/audit.json', $report);
    $failed = array_keys(array_filter($checks, fn ($check) => !$check['preserved']));
    echo json_encode(['preserved_tables' => count($checks) - count($failed), 'failed' => $failed, 'missing_tables' => $missingTables, 'missing_columns' => $columnDifferences, 'stages' => $report['production_stages'], 'progress_rows' => $report['progress_rows']], JSON_PRETTY_PRINT).PHP_EOL;
    if ($failed || $missingTables || $columnDifferences || $definitionDifferences) exit(1);
    exit;
}

if ($mode === 'export') {
    $report = json_decode(file_get_contents($work.'/audit.json'), true, 512, JSON_THROW_ON_ERROR);
    if (array_filter($report['preservation'], fn ($check) => !$check['preserved']) || $report['missing_local_tables'] || $report['missing_local_columns'] || $report['different_local_definitions']) throw new RuntimeException('Audit must pass before export.');
    runClient('mysqldump', $plan['database'], null, $plan['full_sql'], $work.'/export.err', ['--single-transaction', '--quick', '--skip-comments', '--hex-blob', '--skip-add-drop-table', '--skip-triggers', '--complete-insert']);
    echo "Full SQL exported.\n";
    exit;
}

if ($mode === 'verify') {
    $expected = databaseSnapshot();
    $results = [];
    foreach (['patch_database' => [$source, $plan['patch_sql']], 'dump_database' => [$plan['full_sql']]] as $key => $inputs) {
        createIsolatedDatabase($plan[$key]);
        foreach ($inputs as $i => $input) runClient('mysql', $plan[$key], $input, $work.'/'.$key.'-'.$i.'.out', $work.'/'.$key.'-'.$i.'.err', ['--binary-mode=1']);
        isolatedConnection($plan[$key]);
        $snapshot = databaseSnapshot();
        $results[$key] = ['tables' => count($snapshot), 'matches' => $snapshot === $expected];
        if (!$results[$key]['matches']) saveJson($work.'/'.$key.'-snapshot.json', $snapshot);
    }
    isolatedConnection($plan['database']);
    saveJson($work.'/verification.json', $results);
    echo json_encode($results, JSON_PRETTY_PRINT).PHP_EOL;
    if (array_filter($results, fn ($result) => !$result['matches'])) exit(1);
    exit;
}

throw new RuntimeException('Unknown mode.');
