<?php

namespace App\Jobs;

use App\Models\ImportShopifyRun;
use App\Services\Shopify\InitialImport\InitialImportRunner;
use App\Services\Shopify\InitialImport\InitialImportOrderUpdates;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RunInitialShopifyImport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;
    public bool $failOnTimeout = true;
    public int $tries = 0;

    public function __construct(public string $runId)
    {
        $this->onConnection('import_shopify');
        $this->onQueue('import-shopify');
    }

    public function handle(InitialImportRunner $runner): void
    {
        $run = ImportShopifyRun::find($this->runId);
        if (!$run || in_array($run->status, ['completed', 'cancelled', 'failed'], true)) return;
        $lock = Cache::lock('initial-shopify:'.$this->runId, 1000);
        if (!$lock->get()) { $this->release(2); return; }
        try {
            $deadline = microtime(true) + 20;
            while (microtime(true) < $deadline) {
                $run->refresh();
                if (in_array($run->status, ['completed', 'cancelled', 'failed'], true)) return;
                if ($runner->tick($run)) return;
                // The HTTP source already enforces a minimum second between queries.
            }
            $this->release(1);
        } catch (\Throwable $error) {
            $run->update(['status' => 'failed', 'error_code' => 'IMPORT_STOPPED',
                'error_message' => $error instanceof \RuntimeException ? mb_substr($error->getMessage(), 0, 1500) : 'La carga se detuvo por un error interno. El progreso guardado se conserva.', 'heartbeat_at' => now()]);
            app(InitialImportOrderUpdates::class)->fail($run, $run->error_message);
            report($error);
        } finally { $lock->release(); }
    }

    public function failed(?\Throwable $error): void
    {
        ImportShopifyRun::whereKey($this->runId)->whereNotIn('status', ['completed', 'cancelled'])->update([
            'status' => 'failed', 'error_code' => 'WORKER_STOPPED', 'error_message' => 'El proceso se interrumpió. Puedes reanudar desde el último punto guardado.',
        ]);
        $run = ImportShopifyRun::find($this->runId);
        if ($run && $run->status === 'failed') app(InitialImportOrderUpdates::class)->fail($run, 'El procesador se interrumpió. Puedes reintentar sin perder el avance.');
    }
}
