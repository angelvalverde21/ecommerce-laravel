<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{PhotoSession, Store};
use App\Services\PhotoSessions\PhotoPurchaseSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PhotoSessionPurchaseController extends Controller
{
    public function index(Store $store, Request $request, int $photo_session, PhotoPurchaseSource $sources)
    {
        abort_unless($request->user()?->hasAnyRole(['master', 'ceo', 'admin', 'marketing']), 403);
        abort_unless($request->user()->hasRole('master') || $request->user()->stores()->whereKey($store->id)->exists(), 403);
        $session = PhotoSession::where('store_id', $store->id)->findOrFail($photo_session);
        $data = $request->validate(['source_kind' => 'required|in:photographer,model,location,task,general', 'source_id' => 'sometimes|integer|min:1']);
        $existing = isset($data['source_id']) ? $session->purchases()->where('store_id', $store->id)->where('photo_source_kind', $data['source_kind'])->where('photo_source_id', $data['source_id'])->first() : null;
        $source = $existing
            ? ['kind' => $data['source_kind'], 'id' => (int) $data['source_id'], 'name' => $existing->photo_recipient_name, 'recipient_name' => in_array($data['source_kind'], ['task', 'general'], true) ? null : $existing->photo_recipient_name]
            : (isset($data['source_id']) ? $sources->resolve($session, $data['source_kind'], (int) $data['source_id']) : ['kind' => $data['source_kind']]);
        $purchases = $session->purchases()->where('store_id', $store->id)
            ->where(function ($query) use ($source) {
                $query->where(function ($query) use ($source) {
                    $query->where('photo_source_kind', $source['kind'])->when(isset($source['id']), fn ($query) => $query->where('photo_source_id', $source['id']));
                });
                if ($source['kind'] === 'general') $query->orWhereNull('photo_source_kind');
            })
            ->with(['items.unit', 'supplier.user', 'payments.gateway', 'payments.images', 'deliveryTracking'])->withCount('attachments')->latest('id')->get();
        $legacy = Schema::hasTable('photo_session_expenses') ? $session->expenses()->where('source_kind', $source['kind'])->when(isset($source['id']), fn ($query) => $query->where('source_id', $source['id']))->with(['payment.gateway', 'payment.images'])->get() : collect();
        $legacyPaid = $legacy->sum(fn ($expense) => $expense->payment?->status === 'paid' ? (float) $expense->payment->amount * ($expense->payment->direction === 'out' ? 1 : -1) : 0);
        $total = $purchases->sum(fn ($purchase) => $purchase->items->sum(fn ($item) => round((float) $item->quantity * (float) $item->price, 2)));
        $paid = $legacyPaid + $purchases->sum(fn ($purchase) => $purchase->payments->where('status', 'paid')->sum(fn ($payment) => (float) $payment->amount * ($payment->direction === 'out' ? 1 : -1)));
        return responseOk(['source' => $source, 'purchases' => $purchases, 'legacy_expenses' => $legacy, 'total_purchases' => $total, 'total_paid' => $paid]);
    }
}
