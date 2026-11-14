<?php

namespace App\Services\Dashboard;

use App\Models\Manufacture;
use App\Models\ProductionStage;
use App\Models\ProductionStageProgress;

class ProductionStageService
{
    public function forProduction(Manufacture $production)
    {
        $progress = $production->stageProgress()->with(['reviewer:id,name', 'events.user:id,name'])->get()->keyBy('stage_id');
        $counts = $production->purchases()->select('production_stage_id')->selectRaw('COUNT(*) AS purchase_count')
            ->groupBy('production_stage_id')->pluck('purchase_count', 'production_stage_id');

        return ProductionStage::orderBy('position')->get()->map(fn ($section) =>
            $this->present($section, $progress->get($section->id), $production->id, (int) $counts->get($section->id, 0)));
    }

    public function present(ProductionStage $section, ?ProductionStageProgress $progress, int $productionId, ?int $purchaseCount = null): array
    {
        $purchaseCount ??= Manufacture::findOrFail($productionId)->purchases()->where('production_stage_id', $section->id)->count();
        $status = $section->show_in_stepper ? ProductionStageStateService::resolve($progress?->status, $purchaseCount) : null;
        $defaults = [
            'status' => $section->show_in_stepper ? 'pending' : null, 'responsible' => null,
            'expected_end' => null, 'completed_at' => null, 'notes' => null, 'revision' => 0,
            'sample_quantity' => null, 'sample_details' => null, 'sample_photos' => [],
            'sample_review' => 'pending', 'review_notes' => null, 'reviewed_at' => null, 'events' => [],
        ];

        return array_merge($defaults, $progress?->toArray() ?? [], $section->toArray(), [
            'id' => $section->id, 'manufacture_id' => $productionId, 'progress_id' => $progress?->id,
            'status' => $status, 'purchases_count' => $purchaseCount,
            'completed_at' => $status === 'completed' ? $progress?->completed_at : null,
        ]);
    }
}
