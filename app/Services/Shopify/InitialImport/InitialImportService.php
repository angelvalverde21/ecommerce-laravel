<?php

namespace App\Services\Shopify\InitialImport;

use App\Jobs\RunInitialShopifyImport;
use App\Models\ImportShopifyRun;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InitialImportService
{
    public function checks(Store $store): array
    {
        $tables = array_map(fn ($entity) => 'import_shopify_'.$entity, [...InitialImportQueries::ENTITIES, 'runs', 'tasks', 'order_updates']);
        return [
            ['label' => 'Tablas de primera importación', 'ready' => collect($tables)->every(fn ($table) => Schema::hasTable($table))],
            ['label' => 'Conexión Shopify configurada', 'ready' => filled(config('shopify.store')) && filled(config('shopify.initial_import_version')) && filled(config('shopify.token'))],
            ['label' => 'Conexión vinculada a esta tienda', 'ready' => config('shopify.initial_import_store_slug') === $store->slug],
            ['label' => 'Procesamiento en segundo plano', 'ready' => Schema::hasTable('jobs') && config('queue.connections.import_shopify.driver') === 'database'],
        ];
    }

    public function start(Store $store, int $userId): ImportShopifyRun
    {
        foreach ($this->checks($store) as $check) abort_unless($check['ready'], 422, $check['label'].' pendiente.');
        return Cache::lock('initial-shopify-store:'.$store->id, 10)->block(3, function () use ($store, $userId) {
            abort_if(ImportShopifyRun::where('store_id', $store->id)->whereNotIn('status', ['completed', 'cancelled'])->exists(), 409, 'Ya existe una carga pendiente. Continúa o reanuda esa carga.');
            return DB::transaction(function () use ($store, $userId) {
                $run = ImportShopifyRun::create(['id' => (string) Str::uuid(), 'store_id' => $store->id, 'requested_by' => $userId,
                    'shop_domain' => InitialImportRunner::domain(), 'api_version' => config('shopify.initial_import_version'), 'status' => 'queued', 'timeline_version' => 2]);
                foreach (array_keys(InitialImportQueries::ROOTS) as $kind) app(InitialImportWriter::class)->task($run->id, $kind, 'root');
                RunInitialShopifyImport::dispatch($run->id);
                return $run;
            });
        });
    }

    public function resume(Store $store, string $id): ImportShopifyRun
    {
        return Cache::lock('initial-shopify-store:'.$store->id, 10)->block(3, function () use ($store, $id) {
            $run = ImportShopifyRun::where('store_id', $store->id)->findOrFail($id);
            abort_unless($run->status === 'failed' || ($run->status === 'running' && $run->heartbeat_at?->lt(now()->subMinutes(21))), 409, 'La carga ya está activa o finalizada.');
            abort_unless($run->shop_domain === InitialImportRunner::domain() && $run->api_version === config('shopify.initial_import_version'), 422, 'La conexión cambió. Restaura la conexión original.');
            $run->update(['status' => 'queued', 'error_code' => null, 'error_message' => null, 'access_verified' => false]);
            DB::table('import_shopify_tasks')->where('run_id', $run->id)->where('status', 'blocked')->update(['status' => 'queued', 'updated_at' => now()]);
            $update = \App\Models\ImportShopifyOrderUpdate::where('run_id', $run->id)->where('status', 'failed')->latest('id')->first();
            $update?->update(['status' => $update->scan_completed_at ? 'importing' : 'queued', 'error_message' => null]);
            RunInitialShopifyImport::dispatch($run->id);
            return $run;
        });
    }

    public function present(ImportShopifyRun $run): array
    {
        $tasks = DB::table('import_shopify_tasks')->where('run_id', $run->id);
        $completed = (clone $tasks)->where('status', 'completed')->count();
        $total = (clone $tasks)->count();
        $current = (clone $tasks)->whereNotIn('status', ['completed', 'blocked'])->orderBy('id')->first();
        $counts = [];
        foreach (InitialImportQueries::ENTITIES as $entity) $counts[$entity] = DB::table('import_shopify_'.$entity)->where('run_id', $run->id)->count();
        $attachments = DB::table('import_shopify_event_attachments')->where('run_id', $run->id);
        return [...$run->only(['id', 'status', 'current_task', 'error_code', 'error_message', 'created_at', 'updated_at', 'completed_at', 'heartbeat_at']),
            'timeline_version' => (int) $run->timeline_version,
            'timeline_unavailable_orders' => (clone $tasks)->where('kind', 'events')->where('metadata->availability', 'unavailable_comments')->count(),
            'counts' => $counts, 'completed_tasks' => $completed, 'total_tasks' => $total,
            'attachments' => ['total' => $counts['event_attachments'], 'downloaded' => (clone $attachments)->where('status', 'downloaded')->count(), 'failed' => (clone $attachments)->where('status', 'failed')->count()],
            'current' => $current ? ['kind' => $current->kind, 'status' => $current->status, 'processed_rows' => $current->processed_rows, 'expected_rows' => $current->expected_rows] : null,
            'can_resume' => $run->status === 'failed' || ($run->status === 'running' && $run->heartbeat_at?->lt(now()->subMinutes(21))),
        ];
    }
}
