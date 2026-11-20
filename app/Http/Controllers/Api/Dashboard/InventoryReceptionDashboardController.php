<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{InventoryReception, Store};
use App\Services\Inventory\InventoryReceptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryReceptionDashboardController extends Controller
{
    private function manufacture(Store $store, int $id)
    {
        $user = Auth::user();
        abort_unless($user && ($user->stores()->where('stores.id', $store->id)->exists() || $user->hasRole('master')), 403);
        return $store->manufactures()->whereIn('type', ['production', 'order'])->findOrFail($id);
    }

    public function index(Store $store, int $manufacture_id)
    {
        $source = $this->manufacture($store, $manufacture_id);
        return InventoryReception::where('store_id', $store->id)->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->id)->with(['items', 'receiver:id,name', 'reverser:id,name', 'poster:id,name'])
            ->orderByDesc('received_on')->orderByDesc('id')->paginate(10);
    }

    public function store(Store $store, Request $request, int $manufacture_id, InventoryReceptionService $service)
    {
        $source = $this->manufacture($store, $manufacture_id);
        $data = $request->validate(['request_id' => 'required|uuid', ...$this->draftRules()]);
        return responseOk($service->save($store, $source, (int) Auth::id(), $data)->load(['items', 'receiver:id,name']));
    }

    private function draftRules(): array
    {
        return [
            'received_on' => 'required|date_format:Y-m-d|before_or_equal:today',
            'notes' => 'nullable|string|max:2000', 'items' => 'required|array|min:1|max:500',
            'items.*.variant_id' => 'required|integer|distinct', 'items.*.quantity' => 'required|integer|min:1|max:1000000',
            'items.*.comment' => 'nullable|in:Recepcion,Correccion',
        ];
    }

    public function update(Store $store, Request $request, int $manufacture_id, int $reception_id, InventoryReceptionService $service)
    {
        $source = $this->manufacture($store, $manufacture_id);
        $data = $request->validate(['revision' => 'required|integer|min:1', ...$this->draftRules()]);
        return responseOk($service->update($store, $source, $reception_id, $data)->load(['items', 'receiver:id,name']));
    }

    public function post(Store $store, Request $request, int $manufacture_id, int $reception_id, InventoryReceptionService $service)
    {
        $source = $this->manufacture($store, $manufacture_id);
        $data = $request->validate(['revision' => 'required|integer|min:1']);
        $receipt = $service->post($store, $source, $reception_id, (int) Auth::id(), $data['revision']);
        return responseOk($receipt->load(['items', 'receiver:id,name', 'kardexes.variant.product.image', 'kardexes.variant.optionValues', 'kardexes.receptionItem:id,inventory_reception_id', 'kardexes.receptionItem.reception:id,number']));
    }

    public function print(Store $store, int $manufacture_id, int $reception_id)
    {
        $source = $this->manufacture($store, $manufacture_id);
        $receipt = InventoryReception::where('store_id', $store->id)->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->id)->with(['items', 'receiver:id,name', 'poster:id,name'])->findOrFail($reception_id);
        return Pdf::loadView('pdf.inventory-reception', compact('store', 'source', 'receipt'))->setPaper('a4')->stream('recepcion-'.$receipt->number.'.pdf');
    }

    public function reverse(Store $store, Request $request, int $manufacture_id, int $reception_id, InventoryReceptionService $service)
    {
        $source = $this->manufacture($store, $manufacture_id);
        $data = $request->validate(['reason' => 'required|string|min:5|max:2000']);
        return responseOk($service->reverse($store, $source, $reception_id, (int) Auth::id(), $data['reason'])->load(['items', 'receiver:id,name', 'reverser:id,name']));
    }
}
