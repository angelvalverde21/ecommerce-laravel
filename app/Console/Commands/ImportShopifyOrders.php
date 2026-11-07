<?php

namespace App\Console\Commands;

use App\Models\ShopifyOrderImport;
use App\Models\Store;
use App\Services\Shopify\OrderImport\ShopifyOrderImportPlan;
use App\Services\Shopify\OrderImport\ShopifyOrderImportRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class ImportShopifyOrders extends Command
{
    protected $signature = 'shopify:orders-import {store : Slug de la tienda local} {--execute : Ejecutar la importación habilitada} {--resume= : UUID de una ejecución pendiente} {--wait=600 : Segundos máximos de espera por la exportación}';

    protected $description = 'Prepara o ejecuta una importación completa y reanudable de pedidos Shopify.';

    public function handle(ShopifyOrderImportPlan $plan, ShopifyOrderImportRunner $runner): int
    {
        $store = Store::where('slug', $this->argument('store'))->first();
        if (! $store) {
            $this->error('No se encontró la tienda local.');

            return self::FAILURE;
        }
        $preparation = $plan->forStore($store);
        if (! $this->option('execute')) {
            $this->info('Solo preparación: no se consulta Shopify ni se modifican datos.');
            $this->table(['Comprobación', 'Estado'], array_map(fn ($check) => [$check['label'], $check['ready'] ? 'Listo' : 'Pendiente'], $preparation['checks']));

            return self::SUCCESS;
        }

        $lock = null;
        try {
            $plan->assertExecutable($store);
            $wait = filter_var($this->option('wait'), FILTER_VALIDATE_INT);
            if ($wait === false || $wait < 1 || $wait > 3600) {
                throw new RuntimeException('--wait debe estar entre 1 y 3600 segundos.');
            }
            $resume = $this->option('resume');
            if ($resume && ! Str::isUuid($resume)) {
                throw new RuntimeException('El identificador --resume debe ser un UUID.');
            }
            $lock = Cache::lock('shopify-orders-import:'.$store->id, 86400);
            if (! $lock->get()) {
                throw new RuntimeException('Ya hay un proceso ejecutándose para esta tienda.');
            }

            $run = $resume
                ? ShopifyOrderImport::where('store_id', $store->id)->findOrFail($resume)
                : $runner->start($store);
            $this->info('Ejecución: '.$run->id);
            $deadline = time() + $wait;
            do {
                if ($runner->advance($run)) {
                    $this->info('Importación completa.');
                    $this->table(['Importados', 'Omitidos por existir', 'Variantes por vincular', 'Direcciones por revisar'], [[
                        $run->imported_orders, $run->skipped_orders, $run->unmatched_variants, $run->unresolved_addresses,
                    ]]);

                    return self::SUCCESS;
                }
                if (time() + 5 >= $deadline) {
                    break;
                }
                $this->line('Shopify continúa preparando la exportación…');
                sleep(5);
            } while (time() < $deadline);

            $this->warn('La exportación sigue en curso. Reanuda con --execute --resume='.$run->id);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error(get_class($exception) === RuntimeException::class
                ? $exception->getMessage()
                : 'El proceso se detuvo. Se conserva el progreso; revisa la ejecución antes de reanudar.');

            return self::FAILURE;
        } finally {
            $lock?->release();
        }
    }
}
