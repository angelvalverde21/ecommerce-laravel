<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{PhotoContact, PhotoLocation, PhotoSession, Store};
use Illuminate\Support\Facades\DB;
use App\Services\PhotoSessions\PhotoSessionStorage;
use App\Services\PhotoSessions\PhotoLocationAddresses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhotoSessionDashboardController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $filters = $request->validate(['q' => 'sometimes|nullable|string|max:150', 'status' => 'sometimes|in:all,planned,in_progress,editing,completed,archived', 'page' => 'sometimes|integer|min:1']);
        if (!$request->user()->hasAnyRole(['master', 'ceo', 'admin', 'marketing'])) return $this->assignedSessions($store, $request, $filters);
        $query = PhotoSession::where('store_id', $store->id)->with(['responsible:id,name', 'photographerContact:id,name', 'photographers:id,name', 'locations.address.district.province.department', 'photoLocation.address.district.province.department'])
            ->withCount(['assets as photos_count' => fn ($q) => $q->where('status', 'ready')->where('kind', 'photo'), 'assets as videos_count' => fn ($q) => $q->where('status', 'ready')->where('kind', 'video')])
            ->withSum(['assets as total_bytes' => fn ($q) => $q->where('status', 'ready')], 'bytes');
        if (($filters['status'] ?? 'all') !== 'all') $query->where('status', $filters['status']);
        if ($search = trim($filters['q'] ?? '')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('collection', 'like', '%'.$search.'%')->orWhere('location', 'like', '%'.$search.'%'));
        $query->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
            'tasks as overdue_tasks_count' => fn ($q) => $q->where('status', '!=', 'completed')->whereDate('due_date', '<', now('America/Lima')->toDateString())]);
        $page = $query->orderByDesc('shoot_date')->orderByDesc('id')->paginate(20);
        return response()->json([...$page->toArray(), 'can_create' => true]);
    }

    private function assignedSessions(Store $store, Request $request, array $filters)
    {
        $mine = fn ($query) => $query->where('assigned_to', $request->user()->id);
        $query = PhotoSession::where('store_id', $store->id)->whereHas('tasks', $mine)->select(['id', 'name', 'store_id', 'status', 'shoot_date', 'starts_at', 'ends_at', 'collection'])
            ->withCount(['tasks' => $mine, 'tasks as completed_tasks_count' => fn ($q) => $mine($q)->where('status', 'completed'),
                'tasks as overdue_tasks_count' => fn ($q) => $mine($q)->where('status', '!=', 'completed')->whereDate('due_date', '<', now('America/Lima')->toDateString())]);
        if (($filters['status'] ?? 'all') !== 'all') $query->where('status', $filters['status']);
        if ($search = trim($filters['q'] ?? '')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('collection', 'like', '%'.$search.'%'));
        $page = $query->orderByDesc('shoot_date')->orderByDesc('id')->paginate(20);
        return response()->json([...$page->toArray(), 'data' => $page->getCollection()->map(fn ($session) => [...$session->toArray(), 'can_open_workspace' => false]), 'can_create' => false]);
    }

    public function options(Store $store, PhotoSessionStorage $storage)
    {
        return responseOk(['employees' => $store->employees()->join('users', 'users.id', '=', 'employees.user_id')
                ->where('employees.status', true)->where('users.status', true)->orderBy('users.name')->toBase()
                ->get(['employees.user_id as id', 'employees.id as employee_id', 'users.name']),
            'photographers' => PhotoContact::where('store_id', $store->id)->where('kind', 'photographer')->orderBy('name')->get(['id', 'name', 'active']),
            'models' => PhotoContact::where('store_id', $store->id)->where('kind', 'model')->orderBy('name')->get(['id', 'name', 'active']),
            'locations' => PhotoLocation::where('store_id', $store->id)->with(PhotoLocationAddresses::RELATION)->orderBy('name')->get(['id', 'name', 'type', 'active'])
                ->map(fn ($location) => ['id' => $location->id, 'name' => $location->name, 'type' => $location->type, 'active' => $location->active, 'address' => app(PhotoLocationAddresses::class)->present($location)['display_address']]),
            'storage' => ['configured' => $storage->configured(), 'provider' => 'Backblaze B2', 'max_bytes' => config('photo-sessions.max_bytes')]]);
    }

    public function show(Store $store, int $photo_session)
    {
        return responseOk($this->present(PhotoSession::where('store_id', $store->id)->findOrFail($photo_session)));
    }

    public function store(Store $store, Request $request)
    {
        $request->merge(['status' => 'planned']);
        $data = $this->validated($store, $request);
        $session = DB::transaction(function () use ($store, $request, $data) {
            $session = new PhotoSession(collect($data)->except(['model_ids', 'photographer_ids', 'location_ids'])->all());
            $session->store_id = $store->id; $session->created_by = $request->user()->id; $session->save();
            $this->syncAssignments($session, $data);
            return $session;
        });
        return response()->json(['data' => $this->present($session), 'message' => 'Sesión fotográfica creada.'], 201);
    }

    public function update(Store $store, int $photo_session, Request $request)
    {
        $session = PhotoSession::where('store_id', $store->id)->findOrFail($photo_session);
        $data = $this->validated($store, $request);
        DB::transaction(function () use ($session, $data) {
            $session->update(collect($data)->except(['model_ids', 'photographer_ids', 'location_ids'])->all());
            $this->syncAssignments($session, $data);
        });
        return responseOk($this->present($session), 'Sesión actualizada.');
    }

    private function present(PhotoSession $session): array
    {
        $session->load(['responsible:id,name', 'photographerContact:id,name', 'photoLocation.address.district.province.department', 'photographers', 'models.images', 'locations.address.district.province.department']);
        $totals = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('photo_session_expenses')) {
            $totals = DB::table('photo_session_expenses as e')->join('payments as p', 'p.id', '=', 'e.payment_id')
                ->where('e.photo_session_id', $session->id)->where('p.status', 'paid')->groupBy('e.source_kind', 'e.source_id')
                ->select(['e.source_kind', 'e.source_id'])->selectRaw("SUM(CASE WHEN p.direction = 'out' THEN p.amount ELSE -p.amount END) as total")
                ->get()->mapWithKeys(fn ($row) => [$row->source_kind.':'.$row->source_id => (float) $row->total])->all();
        }
        $purchaseTotals = [];
        if (\Illuminate\Support\Facades\Schema::hasColumn('purchases', 'photo_source_kind')) {
            foreach ($session->purchases()->with(['items', 'payments'])->get() as $purchase) {
                $key = $purchase->photo_source_kind ? $purchase->photo_source_kind.':'.$purchase->photo_source_id : 'general:'.$session->id;
                $purchaseTotals[$key] = ($purchaseTotals[$key] ?? 0) + $purchase->items->sum(fn ($item) => round((float) $item->quantity * (float) $item->price, 2));
                $totals[$key] = ($totals[$key] ?? 0) + $purchase->payments->where('status', 'paid')->sum(fn ($payment) => (float) $payment->amount * ($payment->direction === 'out' ? 1 : -1));
            }
        }
        return [...$session->toArray(), 'model_ids' => $session->models->modelKeys(), 'photographer_ids' => $session->photographers->modelKeys(), 'location_ids' => $session->locations->modelKeys(),
            'purchase_totals' => (object) $purchaseTotals,
            'expense_totals' => (object) $totals,
            'locations' => $session->locations->map(fn ($location) => app(PhotoLocationAddresses::class)->present($location))];
    }

    public function assignments(Store $store, int $photo_session, Request $request)
    {
        $kind = $request->validate(['kind' => 'required|in:photographer,model,location'])['kind'];
        $table = $kind === 'location' ? 'photo_locations' : 'photo_contacts';
        $exists = Rule::exists($table, 'id')->where('store_id', $store->id);
        if ($kind !== 'location') $exists->where('kind', $kind);
        $data = $request->validate(['mode' => 'required|in:attach,detach', 'ids' => 'required|array|min:1|max:100', 'ids.*' => ['required', 'integer', 'distinct', $exists]]);
        $session = DB::transaction(function () use ($store, $photo_session, $kind, $data) {
            $session = PhotoSession::where('store_id', $store->id)->lockForUpdate()->findOrFail($photo_session);
            $relation = ['photographer' => 'photographers', 'model' => 'models', 'location' => 'locations'][$kind];
            if ($data['mode'] === 'attach') $session->$relation()->syncWithoutDetaching($data['ids']);
            else $session->$relation()->detach($data['ids']);
            if ($kind !== 'model') {
                $field = $kind === 'location' ? 'photo_location_id' : 'photographer_contact_id';
                $session->$field = $session->$relation()->orderBy($kind === 'location' ? 'photo_locations.id' : 'photo_contacts.id')->first()?->id;
                $session->save();
            }
            return $session;
        });
        return responseOk($this->present($session));
    }

    private function syncAssignments(PhotoSession $session, array $data): void
    {
        if (array_key_exists('model_ids', $data)) $session->models()->sync($data['model_ids']);
        foreach (['photographers' => ['photographer_ids', 'photographer_contact_id'], 'locations' => ['location_ids', 'photo_location_id']] as $relation => [$array, $legacy]) {
            $ids = $data[$array] ?? (array_key_exists($legacy, $data) ? ($data[$legacy] ? [$data[$legacy]] : []) : null);
            if ($ids !== null) { $session->$relation()->sync($ids); $session->$legacy = $ids[0] ?? null; }
        }
        if ($session->isDirty()) $session->save();
    }

    private function validated(Store $store, Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:180', 'status' => 'required|in:planned,in_progress,editing,completed,archived',
            'shoot_date' => 'nullable|date_format:Y-m-d', 'starts_at' => 'nullable|required_with:ends_at|date_format:H:i', 'ends_at' => 'nullable|date_format:H:i|after:starts_at',
            'delivery_date' => ['nullable', 'date_format:Y-m-d', ...($request->filled('shoot_date') ? ['after_or_equal:shoot_date'] : [])],
            'responsible_id' => ['nullable', 'integer', Rule::exists('employees', 'user_id')->where('store_id', $store->id)->where('status', true), Rule::exists('users', 'id')->where('status', true)],
            'photo_location_id' => ['nullable', 'integer', Rule::exists('photo_locations', 'id')->where('store_id', $store->id)],
            'photographer_contact_id' => ['nullable', 'integer', Rule::exists('photo_contacts', 'id')->where('store_id', $store->id)->where('kind', 'photographer')],
            'model_ids' => 'sometimes|array|max:100',
            'model_ids.*' => ['integer', 'distinct', Rule::exists('photo_contacts', 'id')->where('store_id', $store->id)->where('kind', 'model')],
            'photographer_ids' => 'sometimes|array|max:100',
            'photographer_ids.*' => ['integer', 'distinct', Rule::exists('photo_contacts', 'id')->where('store_id', $store->id)->where('kind', 'photographer')],
            'location_ids' => 'sometimes|array|max:100',
            'location_ids.*' => ['integer', 'distinct', Rule::exists('photo_locations', 'id')->where('store_id', $store->id)],
            'location' => 'nullable|string|max:255', 'collection' => 'nullable|string|max:255', 'photographer' => 'nullable|string|max:255',
            'team' => 'nullable|string|max:5000', 'brief' => 'nullable|string|max:10000', 'notes' => 'nullable|string|max:10000', 'budget' => 'nullable|numeric|min:0|max:9999999999.99',
        ]);
    }
}
