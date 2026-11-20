<?php

namespace App\Services\Dashboard;

use App\Models\Manufacture;
use App\Models\ProductionStage;
use App\Models\ProductionStageProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProductionStageService
{
    public static function supportsSelection(): bool
    {
        return Schema::hasColumn('production_stage_progress', 'is_enabled');
    }

    public function enabledIds(Manufacture $production)
    {
        return self::supportsSelection()
            ? $production->stageProgress()->where('is_enabled', true)->pluck('stage_id')
            : ProductionStage::pluck('id');
    }

    public function validateSelection(array $ids): void
    {
        $codes = ProductionStage::whereIn('id', $ids)->pluck('code');
        if (!$ids || count(array_unique($ids)) !== count($ids) || $codes->count() !== count($ids)) {
            throw ValidationException::withMessages(['stage_ids' => 'Selecciona al menos una sección válida, sin duplicados.']);
        }
    }

    public function initialize(Manufacture $production, ?array $ids = null): void
    {
        if ($ids !== null) $this->validateSelection($ids);
        foreach (ProductionStage::when($ids !== null, fn ($query) => $query->whereIn('id', $ids))->orderBy('position')->get() as $section) {
            $production->stageProgress()->firstOrCreate(['stage_id' => $section->id]);
        }
    }

    public function configuration(Manufacture $production): array
    {
        $progress = $production->stageProgress()->get()->keyBy('stage_id');
        $counts = $production->purchases()->select('production_stage_id')->selectRaw('COUNT(*) AS purchase_count')
            ->groupBy('production_stage_id')->pluck('purchase_count', 'production_stage_id');
        return [
            'revision' => (int) $production->stage_configuration_revision,
            'options' => ProductionStage::orderBy('position')->get()->map(fn ($stage) => array_merge($stage->toArray(), [
                'is_enabled' => (bool) $progress->get($stage->id)?->is_enabled,
                'purchases_count' => (int) $counts->get($stage->id, 0),
            ])),
        ];
    }

    public function configure(Manufacture $production, array $ids, int $revision): array
    {
        return DB::transaction(function () use ($production, $ids, $revision) {
            $production = $production->newQuery()->whereKey($production->id)->lockForUpdate()->firstOrFail();
            if ((int) $production->stage_configuration_revision !== $revision) {
                throw ValidationException::withMessages(['revision' => 'Otra persona cambió las secciones. Vuelve a abrir su configuración.']);
            }
            $this->validateSelection($ids);
            $before = $this->enabledIds($production);
            $removed = $before->diff($ids);
            if ($production->purchases()->whereIn('production_stage_id', $removed)->exists()) {
                throw ValidationException::withMessages(['stage_ids' => 'Reasigna las compras de una sección antes de quitarla.']);
            }
            foreach (ProductionStage::orderBy('position')->get() as $section) {
                $enabled = in_array((int) $section->id, $ids, true);
                $progress = $production->stageProgress()->where('stage_id', $section->id)->first();
                if (!$progress && !$enabled) continue;
                if (!$progress) $progress = $production->stageProgress()->create(['stage_id' => $section->id, 'is_enabled' => false]);
                if ($progress->is_enabled === $enabled) continue;
                $previous = $progress->is_enabled;
                $progress->is_enabled = $enabled;
                $progress->revision++;
                $progress->save();
                $progress->events()->create(['user_id' => Auth::guard('api')->id() ?? Auth::id(), 'changes' => ['is_enabled' => ['before' => $previous, 'after' => $enabled]]]);
            }
            if ($before->sort()->values()->all() !== collect($ids)->sort()->values()->all()) {
                $production->stage_configuration_revision = $revision + 1;
                $production->save();
            }
            return $this->configuration($production);
        });
    }

    public function forProduction(Manufacture $production)
    {
        $progress = $production->stageProgress()->with(['reviewer:id,name', 'events.user:id,name'])->get()->keyBy('stage_id');
        $counts = $production->purchases()->select('production_stage_id')->selectRaw('COUNT(*) AS purchase_count')
            ->groupBy('production_stage_id')->pluck('purchase_count', 'production_stage_id');

        return ProductionStage::whereIn('id', $this->enabledIds($production))->orderBy('position')->get()->map(fn ($section) =>
            $this->present($section, $progress->get($section->id), $production->id, (int) $counts->get($section->id, 0)));
    }

    public function present(ProductionStage $section, ?ProductionStageProgress $progress, int $productionId, ?int $purchaseCount = null): array
    {
        $purchaseCount ??= Manufacture::findOrFail($productionId)->purchases()->where('production_stage_id', $section->id)->count();
        $manual = $section->show_in_stepper && in_array($section->code, ['reception', 'others'], true);
        $status = $manual ? ($progress?->status ?? 'pending') : ($section->show_in_stepper ? ProductionStageStateService::resolve($progress?->status, $purchaseCount, $section->code === 'molds' && (bool) $progress?->reuses_mold) : null);
        $defaults = [
            'status' => $section->show_in_stepper ? 'pending' : null, 'responsible' => null, 'reuses_mold' => false,
            'expected_end' => null, 'completed_at' => null, 'notes' => null, 'revision' => 0,
            'sample_quantity' => null, 'sample_details' => null, 'sample_photos' => [],
            'sample_review' => 'pending', 'review_notes' => null, 'reviewed_at' => null, 'events' => [],
        ];

        return array_merge($defaults, $progress?->toArray() ?? [], $section->toArray(), [
            'id' => $section->id, 'manufacture_id' => $productionId, 'progress_id' => $progress?->id,
            'status' => $status, 'purchases_count' => $purchaseCount,
            'locked' => false, 'lock_reason' => null,
            'manual_status' => $manual,
            'completed_at' => $status === 'completed' ? $progress?->completed_at : null,
        ]);
    }
}
