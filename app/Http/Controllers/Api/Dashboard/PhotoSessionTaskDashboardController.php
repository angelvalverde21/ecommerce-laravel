<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{PhotoSession, Task, Store, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PhotoSessionTaskDashboardController extends Controller
{
    private const STATUSES = ['pending', 'in_progress', 'blocked', 'completed'];

    public function index(Store $store, Request $request)
    {
        $this->authorizeStore($store, $request);
        $filters = $request->validate(['photo_session_id' => 'required|integer|min:1', 'status' => ['sometimes', Rule::in(['all', 'overdue', ...self::STATUSES])], 'page' => 'sometimes|integer|min:1']);
        $query = Task::where('store_id', $store->id)->whereHasMorph('taskable', [PhotoSession::class], fn ($q) => $q->where('store_id', $store->id));
        if (isset($filters['photo_session_id'])) {
            PhotoSession::where('store_id', $store->id)->findOrFail($filters['photo_session_id']);
            $query->where('taskable_id', $filters['photo_session_id']);
        }
        if (!isset($filters['photo_session_id']) || !$this->canViewTeam($request->user())) {
            if (!$this->canManage($request->user())) $this->authorizeEmployee($store, $request);
            abort_unless((clone $query)->where('assigned_to', $request->user()->id)->exists(), 403);
            $query->where('assigned_to', $request->user()->id);
        }
        $summaryQuery = clone $query;
        $summary = ['total' => (clone $summaryQuery)->count()];
        foreach (self::STATUSES as $status) $summary[$status] = (clone $summaryQuery)->where('status', $status)->count();
        $summary['overdue'] = (clone $summaryQuery)->where('status', '!=', 'completed')->whereDate('due_date', '<', $this->today())->count();
        if (($filters['status'] ?? 'all') === 'overdue') $query->where('status', '!=', 'completed')->whereDate('due_date', '<', $this->today());
        elseif (($filters['status'] ?? 'all') !== 'all') $query->where('status', $filters['status']);
        $page = $query->with(['assignee:id,name', 'taskable:id,name,store_id,shoot_date'])->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')->orderBy('due_date')->orderBy('id')->paginate(20);
        $page->getCollection()->transform(fn ($task) => $this->present($task, $store, $request));
        $canManage = $this->canManage($request->user());
        return response()->json([...$page->toArray(), 'session' => PhotoSession::where('store_id', $store->id)->findOrFail($filters['photo_session_id'])->only(['id', 'name', 'shoot_date']), 'summary' => $summary, 'can_manage' => $canManage,
            'assignees' => $canManage ? $store->employees()->join('users', 'users.id', '=', 'employees.user_id')->where('employees.status', true)->where('users.status', true)->orderBy('users.name')->toBase()->get(['users.id', 'users.name']) : []]);
    }

    public function store(Store $store, Request $request)
    {
        $this->authorizeStore($store, $request);
        abort_unless($this->canManage($request->user()), 403);
        $data = $request->validate(['photo_session_id' => ['required', 'integer', Rule::exists('photo_sessions', 'id')->where('store_id', $store->id)], ...$this->planningRules($store)]);
        $task = DB::transaction(function () use ($store, $data, $request) {
            $task = Task::create([...collect($data)->except('photo_session_id')->all(), 'store_id' => $store->id, 'taskable_type' => 'photo_session', 'taskable_id' => $data['photo_session_id'], 'created_by' => $request->user()->id, 'status' => 'pending']);
            $this->record($task, $request, 'created', ['after' => $task->only(['title', 'description', 'assigned_to', 'due_date', 'status'])]);
            return $task;
        });
        return response()->json(['data' => $this->present($task, $store, $request)], 201);
    }

    public function update(Store $store, int $task_id, Request $request)
    {
        $this->authorizeStore($store, $request);
        abort_unless($this->canManage($request->user()), 403);
        $data = $request->validate([...$this->planningRules($store), 'version' => 'required|integer|min:1']);
        $task = DB::transaction(function () use ($store, $task_id, $request, $data) {
            $task = $this->find($store, $task_id, true);
            $this->checkVersion($task, $data['version']);
            $before = $task->only(['title', 'description', 'assigned_to', 'due_date']);
            $task->fill(collect($data)->except('version')->all());
            if ($task->isDirty()) {
                $task->version++; $task->save();
                $this->record($task, $request, 'updated', ['before' => $before, 'after' => $task->only(array_keys($before))]);
            }
            return $task;
        });
        return responseOk($this->present($task, $store, $request));
    }

    public function destroy(Store $store, int $task_id, Request $request)
    {
        $this->authorizeStore($store, $request);
        abort_unless($this->canManage($request->user()), 403);
        $data = $request->validate(['version' => 'required|integer|min:1']);
        DB::transaction(function () use ($store, $task_id, $data) {
            $task = $this->find($store, $task_id, true);
            $this->checkVersion($task, $data['version']);
            $task->events()->delete();
            $task->delete();
        });
        return responseOk(['id' => $task_id], 'Tarea eliminada.');
    }

    public function status(Store $store, int $task_id, Request $request)
    {
        $this->authorizeStore($store, $request);
        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES)], 'version' => 'required|integer|min:1', 'note' => 'nullable|required_if:status,blocked|string|max:2000']);
        $task = DB::transaction(function () use ($store, $task_id, $request, $data) {
            $task = $this->find($store, $task_id, true);
            abort_unless($this->canUpdate($task, $store, $request), 403);
            $this->checkVersion($task, $data['version']);
            $before = $task->status;
            if ($before === $data['status'] && !($data['note'] ?? null)) return $task;
            $task->status = $data['status'];
            $task->last_note = $data['note'] ?? null;
            $task->completed_at = $task->status === 'completed' ? ($task->completed_at ?? now()) : null;
            $task->completed_by = $task->status === 'completed' ? ($task->completed_by ?? $request->user()->id) : null;
            $task->version++; $task->save();
            $this->record($task, $request, 'status_changed', ['before' => ['status' => $before], 'after' => ['status' => $task->status]], $data['note'] ?? null);
            return $task;
        });
        return responseOk($this->present($task, $store, $request));
    }

    public function events(Store $store, int $task_id, Request $request)
    {
        $this->authorizeStore($store, $request);
        $task = $this->find($store, $task_id);
        abort_unless($this->canViewTeam($request->user()) || $this->canUpdate($task, $store, $request), 403);
        return responseOk($task->events()->with('actor:id,name')->orderByDesc('id')->get());
    }

    private function planningRules(Store $store): array
    {
        return ['title' => 'required|string|max:180', 'description' => 'nullable|string|max:5000', 'due_date' => 'nullable|date_format:Y-m-d',
            'assigned_to' => ['required', 'integer', Rule::exists('employees', 'user_id')->where('store_id', $store->id)->where('status', true), Rule::exists('users', 'id')->where('status', true)]];
    }
    private function find(Store $store, int $id, bool $lock = false): Task
    {
        $query = Task::where('store_id', $store->id)->whereHasMorph('taskable', [PhotoSession::class], fn ($q) => $q->where('store_id', $store->id));
        return ($lock ? $query->lockForUpdate() : $query)->findOrFail($id);
    }
    private function authorizeStore(Store $store, Request $request): void
    {
        abort_unless($request->user()->hasRole('master') || $request->user()->stores()->whereKey($store->id)->exists(), 403);
    }
    private function authorizeEmployee(Store $store, Request $request): void
    {
        abort_unless(DB::table('employees')->where('store_id', $store->id)->where('user_id', $request->user()->id)->where('status', true)->exists(), 403);
    }
    private function canManage(User $user): bool { return $user->hasAnyRole(['master', 'ceo']); }
    private function canViewTeam(User $user): bool { return $user->hasAnyRole(['master', 'ceo', 'admin', 'marketing']); }
    private function canUpdate(Task $task, Store $store, Request $request): bool
    {
        return $this->canManage($request->user()) || ($task->assigned_to === $request->user()->id
            && DB::table('employees')->where('store_id', $store->id)->where('user_id', $request->user()->id)->where('status', true)->exists());
    }
    private function checkVersion(Task $task, int $version): void
    {
        abort_if($task->version !== $version, 409, 'Esta tarea cambió mientras la editabas. Actualiza la lista antes de guardar.');
    }
    private function record(Task $task, Request $request, string $type, array $changes, ?string $note = null): void
    {
        $task->events()->create(['actor_id' => $request->user()->id, 'type' => $type, 'changes' => $changes, 'note' => $note, 'created_at' => now()]);
    }
    private function present(Task $task, Store $store, Request $request): array
    {
        $task->loadMissing(['assignee:id,name', 'taskable:id,name,store_id,shoot_date']);
        return [...collect($task->toArray())->except('taskable')->all(), 'photo_session_id' => $task->taskable_id,
            'session' => $task->taskable->only(['id', 'name', 'shoot_date']),
            'overdue' => $task->status !== 'completed' && $task->due_date && $task->due_date->format('Y-m-d') < $this->today(), 'can_update' => $this->canUpdate($task, $store, $request)];
    }
    private function today(): string { return now('America/Lima')->toDateString(); }
}

