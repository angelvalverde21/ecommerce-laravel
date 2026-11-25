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
        $data = $request->validate(['source_kind' => 'required|in:photographer,model,location,task', 'source_id' => 'required|integer|min:1']);
        $source = $sources->resolve($session, $data['source_kind'], (int) $data['source_id']);
        $purchases = $session->purchases()->where('store_id', $store->id)->where('photo_source_kind', $source['kind'])->where('photo_source_id', $source['id'])
            ->with(['items.unit', 'supplier.user', 'payments.gateway', 'payments.images', 'deliveryTracking'])->withCount('attachments')->latest('id')->get();
        $legacy = Schema::hasTable('photo_session_expenses') ? $session->expenses()->where('source_kind', $source['kind'])->where('source_id', $source['id'])->with(['payment.gateway', 'payment.images'])->get() : collect();
        $legacyPaid = $legacy->sum(fn ($expense) => $expense->payment?->status === 'paid' ? (float) $expense->payment->amount * ($expense->payment->direction === 'out' ? 1 : -1) : 0);
        $total = $purchases->sum(fn ($purchase) => $purchase->items->sum(fn ($item) => round((float) $item->quantity * (float) $item->price, 2)));
        $paid = $legacyPaid + $purchases->sum(fn ($purchase) => $purchase->payments->where('status', 'paid')->sum(fn ($payment) => (float) $payment->amount * ($payment->direction === 'out' ? 1 : -1)));
        return responseOk(['source' => $source, 'purchases' => $purchases, 'legacy_expenses' => $legacy, 'total_purchases' => $total, 'total_paid' => $paid]);
    }
}
