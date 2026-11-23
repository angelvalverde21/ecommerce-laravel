<?php

namespace App\Services\Dashboard;

use App\Models\{Attachment, DeliveryTracking, Image, InventoryReception, Manufacture, Payment, ProjectProcess, Purchase, Store};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

class ProductionDeletionService
{
    public function delete(Store $store, int $id): void
    {
        DB::transaction(function () use ($store, $id) {
            $production = $store->manufactures()->where('type', 'production')->lockForUpdate()->findOrFail($id);
            if ($production->archived_at) throw ValidationException::withMessages(['production' => 'Restaura la producción archivada antes de eliminarla.']);
            if ($production->kardexes()->exists() || (Schema::hasTable('inventory_receptions') && InventoryReception::whereIn('source_type', [Manufacture::class, $production->getMorphClass()])->where('source_id', $id)->exists())) {
                throw ValidationException::withMessages(['production' => 'Esta producción tiene recepciones o movimientos de inventario. Puedes archivarla para conservar su stock e historial.']);
            }
            if (Schema::hasTable('collections_processes')) {
                foreach (ProjectProcess::where('source_type', 'manufacture')->where('source_id', $id)->lockForUpdate()->get() as $process) {
                    foreach ($process->sales()->get() as $sale) {
                        $sale->receipts()->delete();
                        $sale->delete();
                    }
                    foreach ($process->purchases()->get() as $purchase) $this->deletePurchase($purchase);
                    $process->delete();
                }
            }
            foreach ($production->purchases()->get() as $purchase) $this->deletePurchase($purchase);
            foreach ($production->payments()->get() as $payment) $this->deletePayment($payment);
            foreach ($production->stageProgress()->get() as $stage) {
                $this->deleteMediaReferences($stage);
                $stage->events()->delete();
                $stage->delete();
            }
            $this->deleteMediaReferences($production);
            $production->manufactureVariants()->delete();
            if (Schema::hasTable('manufacture_product')) $production->products()->detach();
            $production->delete();
        }, 3);
    }

    private function deletePurchase(Purchase $purchase): void
    {
        foreach ($purchase->payments()->get() as $payment) $this->deletePayment($payment);
        if (Schema::hasTable('delivery_trackings')) {
            foreach (DeliveryTracking::whereIn('trackable_type', [Purchase::class, $purchase->getMorphClass()])->where('trackable_id', $purchase->id)->get() as $tracking) {
                $tracking->events()->delete();
                $tracking->delete();
            }
        }
        $this->deleteMediaReferences($purchase);
        $purchase->items()->delete();
        $purchase->delete();
    }

    private function deletePayment(Payment $payment): void
    {
        $this->deleteMediaReferences($payment);
        $payment->delete();
    }

    private function deleteMediaReferences(Model $owner): void
    {
        $types = [$owner::class, $owner->getMorphClass()];
        if (Schema::hasTable('attachments')) Attachment::whereIn('attachable_type', $types)->where('attachable_id', $owner->getKey())->delete();
        if (Schema::hasTable('images')) Image::whereIn('imageable_type', $types)->where('imageable_id', $owner->getKey())->delete();
    }
}
