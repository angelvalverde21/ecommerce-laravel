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
    private function production(Store $store, int $productionId)
    {
        $user = Auth::user();
        abort_unless($user && ($user->stores()->where('stores.id', $store->id)->exists() || $user->hasRole('master')), 403);
        return $store->manufactures()->where('type', 'production')->findOrFail($productionId);
    }

    public function index(Store $store, int $production_id)
    {
        return responseOk($this->production($store, $production_id)->stages()
            ->with(['reviewer:id,name', 'events' => fn ($query) => $query->limit(100), 'events.user:id,name'])->get());
    }

    public function update(Store $store, Request $request, int $production_id, int $stage_id)
    {
        $production = $this->production($store, $production_id);
        $data = $request->validate([
            'revision' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'not_applicable'])],
            'responsible' => 'nullable|string|max:255',
            'expected_end' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:5000',
            'date_change_reason' => 'nullable|string|max:2000',
            'sample_quantity' => 'nullable|integer|min:1|max:100000',
            'sample_details' => 'nullable|string|max:5000',
            'sample_photos' => 'nullable|array|max:10',
            'sample_photos.*' => 'required|url:http,https|max:2000',
            'sample_review' => ['sometimes', Rule::in(['pending', 'changes_requested', 'approved'])],
            'review_notes' => 'nullable|string|max:5000',
        ]);

        $stage = DB::transaction(function () use ($production, $stage_id, $data) {
            $stage = $production->stages()->lockForUpdate()->findOrFail($stage_id);
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
            if ($data['status'] === 'not_applicable' && empty(trim($data['notes'] ?? ''))) {
                throw ValidationException::withMessages(['notes' => 'Indica por qué no aplica esta etapa.']);
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

        return responseOk($stage->load(['reviewer:id,name', 'events.user:id,name']));
    }

    public function assignPurchase(Store $store, Request $request, int $production_id, int $stage_id, int $purchase_id)
    {
        $production = $this->production($store, $production_id);
        $stage = $production->stages()->findOrFail($stage_id);
        $purchase = $production->purchases()->findOrFail($purchase_id);
        $purchase->update(['production_stage_id' => $stage->id]);

        return responseOk($purchase->load(['supplier.user', 'items.unit']));
    }
}
