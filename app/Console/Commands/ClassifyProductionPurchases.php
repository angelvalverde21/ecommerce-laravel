<?php

namespace App\Console\Commands;

use App\Models\Manufacture;
use App\Services\Dashboard\ProductionPurchaseClassifier;
use Illuminate\Console\Command;

class ClassifyProductionPurchases extends Command
{
    protected $signature = 'productions:classify-purchases {--apply : Guardar las asignaciones} {--production= : ID de producción}';
    protected $description = 'Clasifica compras pendientes por sus conceptos; por defecto solo muestra la propuesta';

    public function handle(ProductionPurchaseClassifier $classifier): int
    {
        $query = Manufacture::where('type', 'production');
        if ($id = $this->option('production')) { $query->whereKey($id); }
        $assigned = $remaining = 0;
        foreach ($query->cursor() as $production) {
            $result = $classifier->classify($production, (bool) $this->option('apply'));
            if ($result['rows']) { $this->table(['Producción', 'Compra', 'Conceptos', 'Grupo'], $result['rows']); }
            $assigned += $result['assigned'];
            $remaining += $result['remaining'];
        }
        $this->info(($this->option('apply') ? 'Asignadas: ' : 'Propuestas: ').$assigned.'; pendientes de revisión: '.$remaining);
        return self::SUCCESS;
    }
}
