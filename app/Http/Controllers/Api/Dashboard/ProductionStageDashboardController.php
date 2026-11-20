<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ProductionStage;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductionStageDashboardController extends Controller
{
    public function templates(Store $store)
    {
        $this->authorizeStore($store);
        return responseOk(ProductionStage::orderBy('position')->get());
    }

    private function authorizeStore(Store $store): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->stores()->where('stores.id', $store->id)->exists() || $user->hasRole('master')), 403);
    }

    public function configuration(Store $store, int $production_id)
    {
        return responseOk(app(\App\Services\Dashboard\ProductionStageService::class)->configuration($this->production($store, $production_id)));
    }

    public function configure(Store $store, Request $request, int $production_id)
    {
        $production = $this->production($store, $production_id);
        $data = $request->validate([
            'stage_ids' => 'required|array|min:1', 'stage_ids.*' => 'required|integer|distinct|exists:production_stages,id',
            'revision' => 'required|integer|min:0',
        ]);
        return responseOk(app(\App\Services\Dashboard\ProductionStageService::class)->configure($production, array_map('intval', $data['stage_ids']), $data['revision']));
    }

    private function requireEnabled($production, ProductionStage $section): void
    {
        if (!app(\App\Services\Dashboard\ProductionStageService::class)->enabledIds($production)->contains($section->id)) {
            throw ValidationException::withMessages(['production_stage_id' => 'Esta sección no está incluida en la producción.']);
        }
    }
    public function classifyPurchases(Store $store, int $production_id, \App\Services\Dashboard\ProductionPurchaseClassifier $classifier)
    {
        $production = $this->production($store, $production_id);
        $result = $classifier->classify($production);
        return responseOk([
            'assigned' => $result['assigned'], 'remaining' => $result['remaining'],
            'purchases' => $production->purchases()->withCount('attachments')->with(['supplier.user', 'items.unit', 'payments.images', 'deliveryTracking'])->get(),
        ]);
    }

    private function production(Store $store, int $productionId)
    {
        $this->authorizeStore($store);
        return $store->manufactures()->where('type', 'production')->findOrFail($productionId);
    }

    public function index(Store $store, int $production_id)
    {
        return responseOk(app(\App\Services\Dashboard\ProductionStageService::class)->forProduction($this->production($store, $production_id)));
    }

    public function update(Store $store, Request $request, int $production_id, string $stage_id)
    {
        $production = $this->production($store, $production_id);
        $definition = $this->section($stage_id);
        abort_unless($definition->show_in_stepper, 422, 'Otros gastos no tiene estado de avance.');
        $data = $request->validate([
            'revision' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
            'responsible' => 'nullable|string|max:255',
            'expected_end' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:5000',
            'reuses_mold' => 'sometimes|boolean',
            'date_change_reason' => 'nullable|string|max:2000',
            'sample_quantity' => 'nullable|integer|min:1|max:100000',
            'sample_details' => 'nullable|string|max:5000',
            'sample_photos' => 'nullable|array|max:10',
            'sample_photos.*' => 'required|url:http,https|max:2000',
            'sample_review' => ['sometimes', Rule::in(['pending', 'changes_requested', 'approved'])],
            'review_notes' => 'nullable|string|max:5000',
        ]);

        $stage = DB::transaction(function () use ($production, $definition, $data) {
            $production->newQuery()->whereKey($production->id)->lockForUpdate()->firstOrFail();
            $this->requireEnabled($production, $definition);
            $manual = in_array($definition->code, ['reception', 'others'], true);
            if ($definition->code !== 'molds') unset($data['reuses_mold']);
            $reusesMold = $definition->code === 'molds' && ($data['reuses_mold'] ?? $production->stageProgress()->where('stage_id', $definition->id)->value('reuses_mold') ?? false);
            $purchaseCount = $production->purchases()->where('production_stage_id', $definition->id)->count();
            if (!$manual && !$reusesMold && $data['status'] === 'completed' && !$purchaseCount) {
                throw ValidationException::withMessages(['status' => 'Registra al menos una compra en esta etapa antes de completarla.']);
            }
            if (!$manual && $data['status'] !== 'completed') {
                $data['status'] = \App\Services\Dashboard\ProductionStageStateService::resolve(null, $purchaseCount, $reusesMold);
            }
            $stage = $production->stageProgress()->firstOrCreate(['stage_id' => $definition->id]);
            $stage->refresh()->setRelation('stage', $definition);
            if ($stage->revision !== $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'Otra persona actualizó esta etapa. Recarga para revisar los cambios.']);
            }
            unset($data['revision']);
            $dateReason = $data['date_change_reason'] ?? null;
            unset($data['date_change_reason']);
            if ($stage->expected_end && array_key_exists('expected_end', $data)
                && $stage->expected_end->format('Y-m-d') !== $data['expected_end'] && empty(trim($dateReason ?? ''))) {
                throw ValidationException::withMessages(['date_change_reason' => 'Indica el motivo del cambio de fecha.']);
            }
            if ($stage->code !== 'samples') {
                foreach (['sample_quantity', 'sample_details', 'sample_photos', 'sample_review', 'review_notes'] as $field) {
                    unset($data[$field]);
                }
            } else {
                $review = $data['sample_review'] ?? $stage->sample_review;
                if ($data['status'] === 'completed' && ($review !== 'approved' || empty($data['sample_quantity'] ?? $stage->sample_quantity))) {
                    throw ValidationException::withMessages(['sample_review' => 'Registra la cantidad y aprueba las muestras antes de finalizar.']);
                }
                if ($review === 'approved' && empty($data['sample_quantity'] ?? $stage->sample_quantity)) {
                    throw ValidationException::withMessages(['sample_quantity' => 'Registra cuántas muestras se aprobaron.']);
                }
                $sampleChanged = collect(['sample_quantity', 'sample_details', 'sample_photos'])->contains(
                    fn ($field) => array_key_exists($field, $data) && $data[$field] != $stage->$field
                );
                if ($review === 'approved' && ($stage->sample_review !== 'approved' || $sampleChanged)) {
                    $data['reviewed_by'] = Auth::id();
                    $data['reviewed_at'] = now();
                } elseif ($review !== 'approved') {
                    $data['reviewed_by'] = null;
                    $data['reviewed_at'] = null;
                }
            }
            $data['completed_at'] = $data['status'] === 'completed' ? ($stage->completed_at ?? now()) : null;
            $before = $stage->only(array_keys($data));
            $stage->fill($data);
            if ($stage->isDirty()) {
                $changes = [];
                foreach ($stage->getDirty() as $field => $value) {
                    $changes[$field] = ['before' => $before[$field] ?? null, 'after' => $stage->$field];
                }
                if ($dateReason) {
                    $changes['date_change_reason'] = ['before' => null, 'after' => $dateReason];
                }
                $stage->revision++;
                $stage->save();
                $stage->events()->create(['user_id' => Auth::id(), 'changes' => $changes]);
            }
            return $stage;
        });

        return responseOk(app(\App\Services\Dashboard\ProductionStageService::class)->present($definition, $stage->load(['reviewer:id,name', 'events.user:id,name']), $production->id));
    }

    public function assignPurchase(Store $store, Request $request, int $production_id, string $stage_id, int $purchase_id)
    {
        $production = $this->production($store, $production_id);
        $stage = $this->section($stage_id);
        $purchase = DB::transaction(function () use ($production, $stage, $purchase_id) {
            $production->newQuery()->whereKey($production->id)->lockForUpdate()->firstOrFail();
            $this->requireEnabled($production, $stage);
            $purchase = $production->purchases()->findOrFail($purchase_id);
            $stageIds = [$purchase->production_stage_id, $stage->id];
            $state = app(\App\Services\Dashboard\ProductionStageStateService::class);
            $state->synchronize($production, $stageIds);
            $purchase->update(['production_stage_id' => $stage->id]);
            $state->synchronize($production, $stageIds);
            return $purchase;
        });

        return responseOk($purchase->load(['supplier.user', 'items.unit', 'payments.images', 'deliveryTracking'])->loadCount('attachments'));
    }

    private function attachmentProgress(Store $store, int $productionId, string $stageId)
    {
        $production = $this->production($store, $productionId);
        $section = $this->section($stageId);
        $this->requireEnabled($production, $section);
        return $production->stageProgress()->firstOrCreate(['stage_id' => $section->id]);
    }

    public function attachments(Store $store, int $production_id, string $stage_id)
    {
        $progress = $this->attachmentProgress($store, $production_id, $stage_id);
        return responseOk($progress->attachments()->latest('id')->get());
    }

    public function uploadAttachment(Store $store, Request $request, int $production_id, string $stage_id)
    {
        $progress = $this->attachmentProgress($store, $production_id, $stage_id);
        return responseOk(app(\App\Services\Dashboard\AttachmentFileService::class)->store($request, $progress));
    }

    public function downloadAttachment(Store $store, int $production_id, string $stage_id, int $attachment_id)
    {
        $progress = $this->attachmentProgress($store, $production_id, $stage_id);
        return app(\App\Services\Dashboard\AttachmentFileService::class)->download($progress, $attachment_id);
    }

    private function section(string $identifier): ProductionStage
    {
        return ctype_digit($identifier)
            ? ProductionStage::findOrFail((int) $identifier)
            : ProductionStage::where('slug', $identifier)->firstOrFail();
    }
}
