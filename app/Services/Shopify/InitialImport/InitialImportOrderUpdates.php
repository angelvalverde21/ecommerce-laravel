<?php

namespace App\Services\Shopify\InitialImport;

use App\Jobs\RunInitialShopifyImport;
use App\Models\ImportShopifyOrderUpdate;
use App\Models\ImportShopifyRun;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Incremental order discovery; detail hydration remains in the existing checkpointed runner. */
class InitialImportOrderUpdates
{
    public function start(Store $store, string $runId, int $userId): ImportShopifyOrderUpdate
    {
        foreach (app(InitialImportService::class)->checks($store) as $check) abort_unless($check['ready'], 422, $check['label'].' pendiente.');
        return Cache::lock('initial-shopify-store:'.$store->id, 10)->block(3, function () use ($store, $runId, $userId) {
            return DB::transaction(function () use ($store, $runId, $userId) {
                $run = ImportShopifyRun::where('store_id', $store->id)->lockForUpdate()->findOrFail($runId);
                abort_unless($run->shop_domain === InitialImportRunner::domain() && $run->api_version === config('shopify.initial_import_version'), 422, 'La conexión Shopify no corresponde a esta importación.');
                $active = $this->active($run);
                if ($active) return $active; // A repeated click attaches to the same operation.
                $latest = ImportShopifyOrderUpdate::where('run_id', $run->id)->latest('id')->first();
                $resume = $run->status === 'failed' && $latest?->status === 'failed';
                abort_unless($run->status === 'completed' || $resume, 409, 'Termina o reanuda la importación pendiente antes de buscar nuevos pedidos.');
                abort_if(ImportShopifyRun::where('store_id', $store->id)->whereKeyNot($run->id)->whereNotIn('status', ['completed', 'cancelled'])->exists(), 409, 'Hay otra importación pendiente en esta tienda.');
                if ($resume) {
                    $update = $latest;
                    $update->update(['status' => $update->scan_completed_at ? 'importing' : 'queued', 'error_message' => null]);
                    DB::table('import_shopify_tasks')->where('run_id', $run->id)->where('status', 'blocked')->update(['status' => 'queued', 'updated_at' => now()]);
                } else {
                    $checkpoint = ImportShopifyOrderUpdate::where('run_id', $run->id)->where('status', 'completed')->latest('id')->first();
                    // Overlap protects the boundary between two requests and the original bulk export.
                    $from = ($checkpoint?->search_until ?? $run->created_at->toImmutable())->subDay();
                    $update = ImportShopifyOrderUpdate::create(['run_id' => $run->id, 'requested_by' => $userId, 'status' => 'queued',
                        'search_from' => $from, 'search_until' => now(), 'task_baseline' => DB::table('import_shopify_tasks')->where('run_id', $run->id)->max('id') ?? 0]);
                }
                $run->update(['status' => 'queued', 'access_verified' => false, 'current_task' => 'new_orders', 'completed_at' => null,
                    'error_code' => null, 'error_message' => null, 'heartbeat_at' => now()]);
                RunInitialShopifyImport::dispatch($run->id)->afterCommit();
                return $update;
            });
        });
    }

    public function active(ImportShopifyRun $run): ?ImportShopifyOrderUpdate
    {
        return ImportShopifyOrderUpdate::where('run_id', $run->id)->whereIn('status', ['queued', 'scanning', 'importing'])->latest('id')->first();
    }

    public function scan(ImportShopifyRun $run, ImportShopifyOrderUpdate $update, InitialImportSource $source, InitialImportWriter $writer): void
    {
        $run->update(['status' => 'running', 'current_task' => 'new_orders', 'heartbeat_at' => now()]);
        $update->update(['status' => 'scanning']);
        $page = $source->newOrders($update->search_from, $update->search_until, $update->cursor);
        $next = $page['pageInfo']['endCursor'] ?? null;
        $more = $page['pageInfo']['hasNextPage'];
        if ($more && (!$next || $next === $update->cursor || !$page['nodes'])) throw new RuntimeException('La búsqueda de pedidos no avanzó. Se conserva el último punto de lectura.');
        foreach ($page['nodes'] as $order) {
            if (empty($order['id']) || empty($order['createdAt'])) throw new RuntimeException('Shopify devolvió un pedido incompleto.');
            $created = \Carbon\CarbonImmutable::parse($order['createdAt']);
            if ($created->lt($update->search_from) || $created->gt($update->search_until)) throw new RuntimeException('Shopify devolvió un pedido fuera del intervalo solicitado.');
        }
        DB::transaction(function () use ($run, $update, $writer, $page, $next, $more) {
            $known = DB::table('import_shopify_orders')->where('run_id', $run->id)->whereIn('shopify_id', array_column($page['nodes'], 'id'))->pluck('shopify_id')->flip();
            $added = 0;
            foreach ($page['nodes'] as $order) {
                if ($known->has($order['id'])) continue;
                $writer->save($run, 'orders', $order, includeDependencies: true);
                $known->put($order['id'], true); $added++;
            }
            $update->update(['cursor' => $next, 'checked_orders' => $update->checked_orders + count($page['nodes']),
                'added_orders' => $update->added_orders + $added, 'status' => $more ? 'scanning' : 'importing', 'scan_completed_at' => $more ? null : now()]);
        });
        $run->update(['throttle' => $source->throttle, 'heartbeat_at' => now()]);
    }

    public function present(ImportShopifyOrderUpdate $update): array
    {
        $tasks = DB::table('import_shopify_tasks')->where('run_id', $update->run_id)->where('id', '>', $update->task_baseline);
        return [...$update->only(['id', 'run_id', 'status', 'checked_orders', 'added_orders', 'search_from', 'search_until', 'created_at', 'completed_at', 'error_message']),
            'completed_tasks' => (clone $tasks)->where('status', 'completed')->count(), 'total_tasks' => (clone $tasks)->count(),
            'can_resume' => $update->status === 'failed'];
    }

    public function latest(ImportShopifyRun $run): ?array
    {
        $update = ImportShopifyOrderUpdate::where('run_id', $run->id)->latest('id')->first();
        return $update ? $this->present($update) : null;
    }

    public function fail(ImportShopifyRun $run, string $message): void
    {
        ImportShopifyOrderUpdate::where('run_id', $run->id)->whereIn('status', ['queued', 'scanning', 'importing'])->update(['status' => 'failed', 'error_message' => $message, 'updated_at' => now()]);
    }
}
