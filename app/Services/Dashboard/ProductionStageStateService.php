<?php

namespace App\Services\Dashboard;

use App\Models\Manufacture;
use App\Models\ProductionStage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionStageStateService
{
    public static function resolve(?string $savedStatus, int $purchaseCount): string
    {
        if ($purchaseCount === 0) {
            return 'pending';
        }

        return $savedStatus === 'completed' ? 'completed' : 'in_progress';
    }

    /** Synchronize existing progress only; stages without manual progress remain virtual. */
    public function synchronize(Manufacture $production, array $stageIds): void
    {
        if ($production->type !== 'production') {
            return;
        }
        $stageIds = array_values(array_unique(array_filter($stageIds)));
        if (!$stageIds) {
            return;
        }

        DB::transaction(function () use ($production, $stageIds) {
            $production->newQuery()->whereKey($production->id)->lockForUpdate()->firstOrFail();
            $processIds = ProductionStage::whereIn('id', $stageIds)->where('show_in_stepper', true)->pluck('id');
            $counts = $production->purchases()->whereIn('production_stage_id', $processIds)
                ->select('production_stage_id')->selectRaw('COUNT(*) AS purchase_count')
                ->groupBy('production_stage_id')->pluck('purchase_count', 'production_stage_id');
            foreach ($production->stageProgress()->whereIn('stage_id', $processIds)->orderBy('id')->lockForUpdate()->get() as $progress) {
                $status = self::resolve($progress->status, (int) $counts->get($progress->stage_id, 0));
                if ($progress->status === $status && ($status === 'completed' || !$progress->completed_at)) {
                    continue;
                }
                $before = $progress->only(['status', 'completed_at']);
                $progress->status = $status;
                if ($status !== 'completed') {
                    $progress->completed_at = null;
                }
                $progress->revision++;
                $progress->save();
                $changes = [];
                foreach (['status', 'completed_at'] as $field) {
                    if ($before[$field] != $progress->$field) {
                        $changes[$field] = ['before' => $before[$field], 'after' => $progress->$field];
                    }
                }
                $changes['automatic_reason'] = ['before' => null, 'after' => 'Estado actualizado por las compras asignadas a la etapa.'];
                $progress->events()->create(['user_id' => Auth::guard('api')->id() ?? Auth::id(), 'changes' => $changes]);
            }
        });
    }
}
