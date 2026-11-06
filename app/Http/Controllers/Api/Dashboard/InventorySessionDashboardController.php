<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Inventory, Store};
use App\Services\Inventory\InventoryWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventorySessionDashboardController extends Controller
{
    public function index(Store $store, string $inventory_id)
    {
        $inventory = $store->inventories()->findOrFail($inventory_id);
        return $inventory->sessions()->with(['user:id,name', 'warehouse.branch'])->withCount('counts')->withSum(['counts as counted_quantity' => fn ($query) => $query->where('status', 'valid')], 'quantity')->orderByDesc('started_at')->orderByDesc('id')->paginate(20);
    }

    public function show(Store $store, string $inventory_id, string $session_id)
    {
        return responseOk($store->inventories()->findOrFail($inventory_id)->sessions()->with(['user:id,name', 'warehouse.branch'])->withCount('counts')->withSum(['counts as counted_quantity' => fn ($query) => $query->where('status', 'valid')], 'quantity')->findOrFail($session_id));
    }

    public function store(Store $store, Request $request, string $inventory_id)
    {
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $data = $this->validated($store, $request, true);
        $session = DB::transaction(function () use ($inventory, $data) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($inventory);
            app(InventoryWorkspace::class)->warehouse($inventory, (int) $data['warehouse_id']);
            if ($inventory->adjustments()->whereIn('status', ['approved', 'applied'])->exists()) throw ValidationException::withMessages(['inventory_id' => 'El inventario ya tiene ajustes aprobados o aplicados.']);
            if ($inventory->sessions()->where('user_id', $data['user_id'])->where('status', 'open')->exists()) throw ValidationException::withMessages(['user_id' => 'Este responsable ya tiene una sesión abierta en el inventario.']);
            $session = $inventory->sessions()->create([...$data, 'status' => 'open', 'started_at' => now()]);
            if ($inventory->status === 'draft') $inventory->update(['status' => 'in_progress', 'started_at' => now()]);
            return $session;
        });
        return response()->json(['data' => $session->load(['user:id,name', 'warehouse.branch']), 'message' => 'Sesión abierta.'], 201);
    }

    public function update(Store $store, Request $request, string $inventory_id, string $session_id)
    {
        $inventory = $store->inventories()->findOrFail($inventory_id);
        $data = $this->validated($store, $request);
        $request->validate(['status' => 'required|in:open,closed,cancelled']);
        $session = DB::transaction(function () use ($inventory, $session_id, $request, $data) {
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($inventory);
            $session = $inventory->sessions()->findOrFail($session_id);
            if (isset($data['warehouse_id']) && (int) $data['warehouse_id'] !== (int) $session->warehouse_id) throw ValidationException::withMessages(['warehouse_id' => 'El almacén queda fijo al abrir la sesión. Abre otra sesión para contar en otro almacén.']);
            if ($session->status !== 'open') throw ValidationException::withMessages(['status' => 'La sesión ya está cerrada o cancelada y no puede modificarse.']);
            $status = $request->input('status');
            if ($status === 'open' && $inventory->sessions()->where('user_id', $data['user_id'])->where('status', 'open')->where('id', '!=', $session->id)->exists()) throw ValidationException::withMessages(['user_id' => 'Este responsable ya tiene otra sesión abierta.']);
            $session->update([...$data, 'status' => $status, 'ended_at' => $status === 'open' ? null : now()]);
            return $session;
        });
        return responseOk($session->load(['user:id,name', 'warehouse.branch']), 'Sesión actualizada.');
    }

    private function ensureOpen(Inventory $inventory): void
    {
        if (in_array($inventory->status, ['completed', 'cancelled'], true)) throw ValidationException::withMessages(['inventory_id' => 'Este inventario ya está finalizado o cancelado.']);
    }

    private function validated(Store $store, Request $request, bool $creating = false): array
    {
        $data = $request->validate(['warehouse_id' => ($creating ? 'required' : 'sometimes|required').'|integer|min:1', 'user_id' => 'sometimes|required|integer', 'comment' => 'nullable|string|max:5000']);
        $data['user_id'] = $data['user_id'] ?? Auth::id();
        if ($data['user_id'] !== Auth::id() && !$store->users()->where('users.id', $data['user_id'])->exists()) throw ValidationException::withMessages(['user_id' => 'Selecciona un responsable de esta empresa.']);
        return $data;
    }
}
