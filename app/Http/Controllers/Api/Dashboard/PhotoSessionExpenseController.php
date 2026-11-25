<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Image, PhotoSession, PhotoSessionExpense, Store, Supplier};
use App\Traits\UploadImagesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PhotoSessionExpenseController extends Controller
{
    use UploadImagesTrait;

    private function session(Store $store, Request $request, int $id): PhotoSession
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin', 'marketing']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        return PhotoSession::where('store_id', $store->id)->findOrFail($id);
    }

    private function source(PhotoSession $session, Request $request): array
    {
        $data = $request->validate(['source_kind' => 'required|in:photographer,model,location,task', 'source_id' => 'required|integer|min:1']);
        $relation = match ($data['source_kind']) {
            'photographer' => $session->photographers(), 'model' => $session->models(),
            'location' => $session->locations(), 'task' => $session->tasks(),
        };
        $record = $relation->where('store_id', $session->store_id)->findOrFail($data['source_id']);
        if (in_array($data['source_kind'], ['photographer', 'model'], true)) abort_unless($record->kind === $data['source_kind'], 404);
        return ['kind' => $data['source_kind'], 'id' => $record->id, 'name' => $record->name ?? $record->title,
            'recipient_name' => $data['source_kind'] === 'task' ? null : ($data['source_kind'] === 'location' ? $record->contact_name : $record->name)];
    }

    public function index(Store $store, Request $request, int $photo_session)
    {
        $session = $this->session($store, $request, $photo_session);
        $source = $this->source($session, $request);
        $expenses = $session->expenses()->where('source_kind', $source['kind'])->where('source_id', $source['id'])->with(['payment.gateway', 'payment.images'])->latest('id')->get();
        return responseOk(['source' => $source, 'expenses' => $expenses,
            'suppliers' => $source['kind'] === 'task' ? Supplier::where('store_id', $store->id)->with('user')->get()->map(fn ($supplier) => ['id' => $supplier->id, 'name' => $supplier->user?->name ?: $supplier->name]) : [],
            'total_paid' => $expenses->sum(fn ($expense) => $expense->payment?->status === 'paid' ? (float) $expense->payment->amount * ($expense->payment->direction === 'out' ? 1 : -1) : 0)]);
    }

    public function store(Store $store, Request $request, int $photo_session)
    {
        $session = $this->session($store, $request, $photo_session);
        $source = $this->source($session, $request);
        $data = $request->validate([
            'request_key' => 'required|uuid', 'amount' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'date' => 'required|date_format:Y-m-d|before_or_equal:'.now('America/Lima')->toDateString(),
            'gateway_id' => ['required', 'integer', Rule::exists('gateways', 'id')->where('store_id', $store->id)],
            'direction' => 'sometimes|in:out', 'comment' => 'required|string|max:255', 'reference_code' => 'nullable|string|max:10',
            'supplier_id' => [$source['kind'] === 'task' ? 'required' : 'nullable', 'integer', Rule::exists('suppliers', 'id')->where('store_id', $store->id)],
            'recipient_name' => 'nullable|string|max:255', 'images' => 'sometimes|array|max:5', 'images.*' => 'image|max:2048',
        ]);
        $recipient = $source['recipient_name'];
        if ($source['kind'] === 'task') {
            $supplier = Supplier::where('store_id', $store->id)->findOrFail($data['supplier_id']);
            $recipient = $supplier->user?->name ?: $supplier->name;
        } elseif (!$recipient) $recipient = trim($data['recipient_name'] ?? '');
        abort_unless(filled($recipient), 422, 'Indica a quién se realiza el pago.');
        $hash = hash('sha256', json_encode([$source, $recipient, number_format((float) $data['amount'], 2, '.', ''), $data['date'], (int) $data['gateway_id'], $data['comment'], $data['reference_code'] ?? null, $data['supplier_id'] ?? null]));
        $expense = DB::transaction(function () use ($session, $source, $recipient, $data, $request, $store, $hash) {
            PhotoSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($existing = PhotoSessionExpense::where('request_key', $data['request_key'])->first()) {
                abort_unless($existing->photo_session_id === $session->id && hash_equals($existing->payload_hash, $hash), 409, 'Este registro ya fue procesado con otros datos.');
                return $existing;
            }
            $payment = $session->payments()->create(['store_id' => $store->id, 'user_id' => $request->user()->id,
                'amount' => $data['amount'], 'date' => $data['date'], 'gateway_id' => $data['gateway_id'], 'comment' => $data['comment'],
                'reference_code' => $data['reference_code'] ?? null, 'direction' => 'out', 'status' => 'paid']);
            foreach ($request->file('images', []) as $file) $payment->images()->create($this->getSizeArray($file, Image::DIR_PAYMENT));
            return $session->expenses()->create(['payment_id' => $payment->id, 'source_kind' => $source['kind'], 'source_id' => $source['id'],
                'source_name' => $source['name'], 'recipient_name' => $recipient, 'supplier_id' => $source['kind'] === 'task' ? $data['supplier_id'] : null,
                'request_key' => $data['request_key'], 'payload_hash' => $hash]);
        });
        return responseOk($expense->load(['payment.gateway', 'payment.images']), 'Pago registrado.');
    }
}
