<?php

namespace App\Console\Commands;

use App\Jobs\RunInitialShopifyImport;
use App\Models\ImportShopifyRun;
use App\Services\Shopify\InitialImport\InitialImportRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefreshImportShopifyTimeline extends Command
{
    protected $signature = 'shopify:refresh-import-timeline {run : UUID de la primera importación}';
    protected $description = 'Recupera los eventos y adjuntos disponibles del timeline sin repetir la descarga de pedidos';

    public function handle(): int
    {
        $run = ImportShopifyRun::findOrFail($this->argument('run'));
        return Cache::lock('initial-shopify-store:'.$run->store_id, 10)->block(3, function () use ($run) {
            $lock = Cache::lock('initial-shopify:'.$run->id, 1000);
            if (!$lock->get()) { $this->error('La carga sigue procesándose. Espera a que termine.'); return self::FAILURE; }
            try {
                $run->refresh();
                if (!in_array($run->status, ['completed', 'failed'], true)
                    || $run->shop_domain !== InitialImportRunner::domain()
                    || $run->api_version !== config('shopify.initial_import_version')
                    || ImportShopifyRun::where('store_id', $run->store_id)->whereKeyNot($run->id)->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
                    $this->error('La carga debe estar terminada o detenida, usar su conexión original y no tener otra carga activa.');
                    return self::FAILURE;
                }
                DB::transaction(function () use ($run) {
                    DB::table('import_shopify_orders')->where('run_id', $run->id)->orderBy('id')->chunkById(500, function ($orders) use ($run) {
                        DB::table('import_shopify_tasks')->insertOrIgnore($orders->map(fn ($order) => [
                            'run_id' => $run->id, 'kind' => 'events', 'owner_id' => $order->shopify_id,
                            'status' => 'queued', 'created_at' => now(), 'updated_at' => now(),
                        ])->all());
                    });
                    DB::table('import_shopify_tasks')->where('run_id', $run->id)->where('kind', 'events')->update([
                        'status' => 'queued', 'cursor' => null, 'processed_rows' => 0, 'metadata' => null, 'updated_at' => now(),
                    ]);
                    DB::table('import_shopify_tasks')->where('run_id', $run->id)->where('status', 'blocked')->update(['status' => 'queued', 'updated_at' => now()]);
                    DB::table('import_shopify_tasks')->where('run_id', $run->id)->where('kind', 'event_attachments')->update(['status' => 'queued', 'updated_at' => now()]);
                    $run->update(['status' => 'queued', 'timeline_version' => 2, 'access_verified' => false,
                        'completed_at' => null, 'error_code' => null, 'error_message' => null, 'current_task' => 'events']);
                    RunInitialShopifyImport::dispatch($run->id)->afterCommit();
                });
                $this->info('Timeline y adjuntos en cola. Se conservan los datos y bloques ya importados.');
                return self::SUCCESS;
            } finally { $lock->release(); }
        });
    }
}
