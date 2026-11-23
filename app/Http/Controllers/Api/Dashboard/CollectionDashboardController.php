<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Collection, Manufacture, Order, PhotoSession, ProjectProcess, ProjectSale, Store};
use App\Services\Dashboard\ProductionStageService;
use App\Services\Collections\CollectionWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollectionDashboardController extends Controller
{
    private function authorizeCollection(Store $store, Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        abort_unless(Schema::hasColumn('collections', 'store_id'), 503, 'La sección Colecciones aún no está instalada.');
    }
    private function collection(Store $store, Request $request, int $id): Collection
    {
        $this->authorizeCollection($store, $request);
        return Collection::where('store_id', $store->id)->findOrFail($id);
    }
    private function fail(string $field, string $message): never { throw ValidationException::withMessages([$field => $message]); }
    private function validatedCollection(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:180', 'description' => 'nullable|string|max:5000',
            'status' => 'sometimes|required|in:planning,in_progress,commercializing,closed,archived',
            'starts_on' => 'nullable|date_format:Y-m-d', 'launch_on' => 'nullable|date_format:Y-m-d',
            'ends_on' => 'nullable|date_format:Y-m-d'.($request->filled('starts_on') ? '|after_or_equal:starts_on' : '')]);
    }
    public function index(Store $store, Request $request)
    {
        $this->authorizeCollection($store, $request);
        $data = $request->validate(['q' => 'nullable|string|max:180', 'status' => 'nullable|in:planning,in_progress,commercializing,closed,archived']);
        return response()->json(Collection::where('store_id', $store->id)->when($data['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%'))
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->withCount('processes')->latest('id')->paginate(20));
    }
    public function store(Store $store, Request $request)
    {
        $this->authorizeCollection($store, $request);
        $collection = Collection::create([...$this->validatedCollection($request), 'store_id' => $store->id, 'created_by' => $request->user()->id]);
        return response()->json(['data' => $collection], 201);
    }
    public function show(Store $store, Request $request, int $collection_id, CollectionWorkspace $workspace)
    {
        return responseOk($workspace->snapshot($this->collection($store, $request, $collection_id)));
    }
    public function update(Store $store, Request $request, int $collection_id)
    {
        $collection = $this->collection($store, $request, $collection_id);
        $collection->update($this->validatedCollection($request));
        return responseOk($collection);
    }
    public function options(Store $store, Request $request)
    {
        $this->authorizeCollection($store, $request);
        $data = $request->validate(['kind' => 'required|in:production,order,photo_session,order_sale,responsible', 'q' => 'nullable|string|max:180']);
        $q = trim($data['q'] ?? '');
        if ($data['kind'] === 'responsible') return responseOk(DB::table('users')->join('store_user', 'users.id', '=', 'store_user.user_id')
            ->where('store_user.store_id', $store->id)->orderBy('users.name')->distinct()->get(['users.id', 'users.name']));
        if ($data['kind'] === 'order_sale') {
            if (!app(CollectionWorkspace::class)->ordersReady()) return responseOk([]);
            $orders = Order::where('store_id', $store->id)->when($q, fn ($b) => $b->where(fn ($b) => $b->where('id', $q)->orWhere('shopify_order_name', 'like', '%'.$q.'%')))->latest('id')->limit(20)->get();
            $workspace = app(CollectionWorkspace::class);
            return responseOk($orders->map(fn ($o) => ['id' => $o->id, 'name' => $o->shopify_order_name ?: '#'.$o->id, 'currency' => $o->currency_code ?? 'PEN',
                'lines' => array_map(function ($line) use ($o) {
                    $line['allocated'] = (int) ProjectSale::where('order_id', $o->id)->where('line_key', $line['key'])->sum('quantity');
                    return $line;
                }, $workspace->lines($o))]));
        }
        $photos = $data['kind'] === 'photo_session';
        $query = $photos ? PhotoSession::where('store_id', $store->id) : Manufacture::where('store_id', $store->id)->where('type', $data['kind']);
        return responseOk($query->when($q, fn ($b) => $b->where('name', 'like', '%'.$q.'%'))
            ->whereNotIn('id', ProjectProcess::where('source_type', $photos ? 'photo_session' : 'manufacture')->whereNotNull('source_id')->select('source_id'))
            ->latest('id')->get(['id', 'name', 'created_at']));
    }
    public function process(Store $store, Request $request, int $collection_id)
    {
        $collection = $this->collection($store, $request, $collection_id);
        $data = $request->validate(['kind' => 'required|in:production,order,photo_session,other',
            'source_id' => 'nullable|integer|min:1', 'name' => 'required_without:source_id|nullable|string|max:180', 'notes' => 'nullable|string|max:5000',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'starts_on' => 'nullable|date_format:Y-m-d', 'due_on' => 'nullable|date_format:Y-m-d'.($request->filled('starts_on') ? '|after_or_equal:starts_on' : ''),
            'responsible_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('store_user', 'user_id')->where('store_id', $store->id)]]);

        $process = DB::transaction(function () use ($store, $request, $collection, $data) {
            $kind = $data['kind']; $source = null;
            if ($kind === 'other' && !empty($data['source_id'])) $this->fail('source_id', 'Los procesos propios se crean dentro de la colección.');
            if ($kind === 'production' || $kind === 'order') {
                $source = !empty($data['source_id']) ? Manufacture::where('store_id', $store->id)->where('type', $kind)->lockForUpdate()->findOrFail($data['source_id'])
                    : Manufacture::create(['store_id' => $store->id, 'type' => $kind, 'name' => $data['name'], 'user_id' => $request->user()->id]);
                if (empty($data['source_id']) && $kind === 'production') app(ProductionStageService::class)->initialize($source);
            } elseif ($kind === 'photo_session') {
                if (!empty($data['source_id'])) $source = PhotoSession::where('store_id', $store->id)->lockForUpdate()->findOrFail($data['source_id']);
                else { $source = new PhotoSession; $source->forceFill(['store_id' => $store->id, 'created_by' => $request->user()->id, 'name' => $data['name'], 'status' => 'planned'])->save(); }
            }
            $sourceType = $source instanceof Manufacture ? 'manufacture' : ($source ? 'photo_session' : 'other');
            if ($source && ProjectProcess::where('source_type', $sourceType)->where('source_id', $source->id)->exists()) $this->fail('source_id', 'Este proceso ya pertenece a una colección. Desvincúlalo antes de trasladarlo.');
            return $collection->processes()->create(['store_id' => $store->id, 'kind' => $kind, 'source_type' => $sourceType, 'source_id' => $source?->id,
                'name' => $source?->name ?? $data['name'], 'notes' => $data['notes'] ?? null, 'starts_on' => $data['starts_on'] ?? null, 'due_on' => $data['due_on'] ?? null,
                'responsible_id' => $data['responsible_id'] ?? null, 'status' => $kind === 'other' ? ($data['status'] ?? 'pending') : 'pending']);
        });
        return responseOk($process);
    }
    public function updateProcess(Store $store, Request $request, int $collection_id, int $process_id)
    {
        $collection = $this->collection($store, $request, $collection_id);
        $process = ProjectProcess::where('collection_id', $collection->id)->findOrFail($process_id);
        $data = $request->validate(['collection_id' => 'required|integer', 'status' => 'required|in:pending,in_progress,completed,cancelled', 'notes' => 'nullable|string|max:5000',
            'name' => 'sometimes|required|string|max:180',
            'starts_on' => 'nullable|date_format:Y-m-d', 'due_on' => 'nullable|date_format:Y-m-d'.($request->filled('starts_on') ? '|after_or_equal:starts_on' : ''),
            'responsible_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('store_user', 'user_id')->where('store_id', $store->id)]]);
        Collection::where('store_id', $store->id)->findOrFail($data['collection_id']);
        if ($process->source_type !== 'other') unset($data['status'], $data['name']); // External process fields are read from its own workspace.
        $process->update($data);
        return responseOk($process);
    }
    public function detach(Store $store, Request $request, int $collection_id, int $process_id, CollectionWorkspace $workspace)
    {
        $collection = $this->collection($store, $request, $collection_id);
        $process = ProjectProcess::where('collection_id', $collection->id)->findOrFail($process_id);
        DB::transaction(function () use ($process) {
            ProjectProcess::whereKey($process->id)->lockForUpdate()->firstOrFail();
            if ($process->sales()->exists() || ($process->source_type === 'other' && $process->purchases()->exists())) $this->fail('process_id', 'Conserva este proceso: tiene ventas atribuidas o compras propias. Puedes trasladarlo a otra colección de esta tienda.');
            $process->delete();
        });
        return responseOk(['detached' => true]);
    }
    public function sale(Store $store, Request $request, int $collection_id, CollectionWorkspace $workspace)
    {
        $collection = $this->collection($store, $request, $collection_id);
        abort_unless($workspace->ordersReady(), 422, 'El registro de pedidos aún no está disponible.');
        $data = $request->validate(['process_id' => 'required|integer', 'order_id' => 'required|integer', 'line_key' => 'required|string|max:191', 'quantity' => 'required|integer|min:1']);
        $process = ProjectProcess::where('collection_id', $collection->id)->findOrFail($data['process_id']);
        $order = Order::where('store_id', $store->id)->findOrFail($data['order_id']);
        return responseOk($workspace->allocate($process, $order, $data, $request->user()->id));
    }
    public function removeSale(Store $store, Request $request, int $collection_id, int $sale_id)
    {
        $collection = $this->collection($store, $request, $collection_id);
        $sale = ProjectSale::whereHas('process.collection', fn ($q) => $q->where('id', $collection->id))->findOrFail($sale_id);
        DB::transaction(function () use ($sale) {
            ProjectSale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($sale->receipts()->exists()) $this->fail('sale_id', 'Esta atribución tiene cobros registrados y debe conservarse.');
            $sale->delete();
        });
        return responseOk(['removed' => true]);
    }
    public function receipt(Store $store, Request $request, int $collection_id, int $sale_id, CollectionWorkspace $workspace)
    {
        $collection = $this->collection($store, $request, $collection_id);
        $sale = ProjectSale::whereHas('process.collection', fn ($q) => $q->where('id', $collection->id))->findOrFail($sale_id);
        $data = $request->validate(['amount' => 'required|numeric|min:0.01|max:99999999999', 'date' => 'required|date_format:Y-m-d|before_or_equal:'.now('America/Lima')->toDateString(),
            'direction' => 'required|in:in,out', 'reference' => 'required|string|max:180', 'notes' => 'nullable|string|max:5000']);
        $receipt = DB::transaction(function () use ($sale, $data, $request, $workspace) {
            $sale = ProjectSale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $net = (int) round($sale->receipts()->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END),0) AS net")->value('net') * 100);
            $amount = (int) round($data['amount'] * 100);
            if ($data['direction'] === 'in' && $net + $amount > (int) round($workspace->saleState($sale)['amount'] * 100)) $this->fail('amount', 'El cobro supera el importe neto de la venta atribuida.');
            if ($data['direction'] === 'out' && $amount > $net) $this->fail('amount', 'La devolución supera los cobros registrados para esta venta.');
            if ($sale->receipts()->where('direction', $data['direction'])->where('reference', $data['reference'])->exists()) $this->fail('reference', 'Ya registraste esa referencia para esta venta.');
            return $sale->receipts()->create([...$data, 'created_by' => $request->user()->id]);
        });
        return responseOk($receipt);
    }
}
