<?php

namespace App\Services\Dashboard;

use App\Models\Manufacture;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionProgressService
{
    public function forStore(Store $store, CarbonImmutable $from, CarbonImmutable $now): array
    {
        $required = [
            'manufactures' => ['id', 'store_id', 'type', 'name', 'created_at'],
            'manufacture_variant' => ['manufacture_id', 'variant_id', 'quantity'],
            'kardexes' => ['store_id', 'kardexable_type', 'kardexable_id', 'variant_id', 'direction', 'quantity', 'created_at'],
        ];
        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns)) {
                return ['available' => false, 'items' => [], 'note' => 'El avance estará disponible cuando se complete la estructura de producciones y recepciones.'];
            }
        }

        $productions = DB::table('manufactures')->where('store_id', $store->id)->where('type', 'production')
            ->where('created_at', '<=', $now)->orderByDesc('created_at')->orderByDesc('id')
            ->get(['id', 'name', 'created_at']);
        $ids = $productions->pluck('id');
        $stages = Schema::hasTable('production_stages')
            ? DB::table('production_stages')->whereIn('manufacture_id', $ids)->orderBy('position')->get()->groupBy('manufacture_id')
            : collect();
        $plans = DB::table('manufacture_variant')->whereIn('manufacture_id', $ids)
            ->select('manufacture_id', 'variant_id')->selectRaw('SUM(quantity) AS planned')
            ->groupBy('manufacture_id', 'variant_id')->get()->groupBy('manufacture_id');
        $receipts = DB::table('kardexes')->where('store_id', $store->id)
            ->where('kardexable_type', (new Manufacture)->getMorphClass())->whereIn('kardexable_id', $ids)
            ->where('created_at', '<=', $now)->whereIn('direction', ['in', 'out'])
            ->select('kardexable_id', 'variant_id')
            ->selectRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) AS received")
            ->selectRaw('MAX(created_at) AS last_activity_at')
            ->groupBy('kardexable_id', 'variant_id')->get()->groupBy('kardexable_id');

        $rows = $productions->map(function ($production) use ($plans, $receipts, $stages) {
            $plan = $plans->get($production->id, collect());
            $received = $receipts->get($production->id, collect())->keyBy('variant_id');
            $plannedUnits = 0;
            $completedUnits = 0;
            foreach ($plan as $variant) {
                $quantity = max(0, (int) $variant->planned);
                $plannedUnits += $quantity;
                $completedUnits += min($quantity, max(0, (int) ($received->get($variant->variant_id)?->received ?? 0)));
            }
            $receivedUnits = (int) $received->sum(fn ($row) => max(0, (int) $row->received));
            $pending = $plannedUnits - $completedUnits;
            $status = match (true) {
                $plannedUnits === 0 => 'unplanned',
                $pending === 0 => 'completed',
                $completedUnits > 0 => 'in_progress',
                default => 'pending',
            };

            return [
                'id' => $production->id,
                'name' => $production->name,
                'created_at' => $production->created_at,
                'last_activity_at' => max($production->created_at, $received->max('last_activity_at') ?? $production->created_at),
                'planned_units' => $plannedUnits,
                'received_units' => $receivedUnits,
                'completed_units' => $completedUnits,
                'pending_units' => $pending,
                'extra_units' => max(0, $receivedUnits - $completedUnits),
                'progress_percent' => $this->percentage($completedUnits, $plannedUnits),
                'status' => $status,
                'needs_review' => $received->contains(fn ($row) => $row->received < 0),
                'stages' => $stages->get($production->id, collect())->map(fn ($stage) => [
                    'id' => $stage->id,
                    'code' => $stage->code,
                    'name' => $stage->name,
                    'status' => $stage->status,
                    'responsible' => $stage->responsible,
                    'expected_end' => $stage->expected_end,
                    'sample_quantity' => $stage->sample_quantity,
                    'sample_review' => $stage->sample_review,
                    'overdue' => $stage->expected_end && $stage->expected_end < now('America/Lima')->toDateString()
                        && !in_array($stage->status, ['completed', 'not_applicable']),
                ])->values()->all(),
                'path' => 'manufactures/productions/'.$production->id,
            ];
        });
        $planned = (int) $rows->sum('planned_units');
        $completed = (int) $rows->sum('completed_units');
        $visible = $rows->filter(fn ($row) => $row['status'] !== 'completed' || $row['last_activity_at'] >= $from->toDateTimeString()
            || collect($row['stages'])->contains(fn ($stage) => !in_array($stage['status'], ['completed', 'not_applicable'])))
            ->sortBy(fn ($row) => $row['status'] === 'completed' ? 1 : 0)->values();

        return [
            'available' => true,
            'total_count' => $rows->count(),
            'active_count' => $rows->whereIn('status', ['pending', 'in_progress'])->count(),
            'completed_count' => $rows->where('status', 'completed')->count(),
            'unplanned_count' => $rows->where('status', 'unplanned')->count(),
            'planned_units' => $planned,
            'completed_units' => $completed,
            'pending_units' => $planned - $completed,
            'progress_percent' => $this->percentage($completed, $planned),
            'visible_count' => $visible->count(),
            'items' => $visible->take(6)->all(),
            'note' => 'Avance por unidades recibidas frente al plan de cada variante. Incluye todas las producciones pendientes, aunque sean anteriores al período, y las completadas con movimientos en el período seleccionado.',
        ];
    }

    private function percentage(int $completed, int $planned): ?float
    {
        if (! $planned) {
            return null;
        }

        return $completed >= $planned ? 100.0 : floor($completed / $planned * 1000) / 10;
    }
}
