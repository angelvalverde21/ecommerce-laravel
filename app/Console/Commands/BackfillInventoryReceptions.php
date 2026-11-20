<?php

namespace App\Console\Commands;

use App\Services\Inventory\HistoricalReceptionImporter;
use Illuminate\Console\Command;

class BackfillInventoryReceptions extends Command
{
    protected $signature = 'inventory:backfill-receptions {--store= : ID de tienda} {--dry-run : Analizar sin guardar}';
    protected $description = 'Reconstruye recepciones históricas desde entradas existentes sin modificar stock';

    public function handle(HistoricalReceptionImporter $importer): int
    {
        $store = $this->option('store');
        if ($store !== null && (!ctype_digit((string) $store) || (int) $store < 1)) { $this->error('Indica un ID de tienda válido.'); return self::FAILURE; }
        $result = $importer->run($store === null ? null : (int) $store, (bool) $this->option('dry-run'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return count($result['skipped']) ? self::FAILURE : self::SUCCESS;
    }
}
