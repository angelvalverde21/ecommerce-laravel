<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Manufacture;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufactureProductionService
{

    public function index(Store $store, ?Request $request = null)
    {

        $request ??= request();
        $manufactureOrders = $store->manufactures()->where('type', 'production')
            ->when($request->boolean('archived'), fn ($q) => $q->whereNotNull('archived_at'), fn ($q) => $q->whereNull('archived_at'))
            ->when($request->filled('search'), fn ($q) => $q->searchProduction($request->input('search')))
            ->betweenDates($request->input('start_date'), $request->input('end_date'))
            ->withFinancialSummary()
            ->withExists(['kardexes as has_inventory_movements', 'inventoryReceptions as has_inventory_receptions'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        app(\App\Services\Collections\ManufactureCollectionLabels::class)->attach($manufactureOrders, $store->id);
        return $manufactureOrders->each(fn ($production) => $production->setAttribute('can_delete', !$production->has_inventory_movements && !$production->has_inventory_receptions && !$production->archived_at));
    }

    public function store(Store $store, Request $request)
    {
        //
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'stage_ids' => 'sometimes|required|array|min:1',
            'stage_ids.*' => 'required|integer|distinct|exists:production_stages,id',
        ]);

        $manufacture = DB::transaction(function () use ($store, $data) {
            $manufacture = $store->manufactures()->create([
                'name' => $data['name'],
                'user_id' => Auth::id(),
                'type' => 'production',
            ]);
            app(\App\Services\Dashboard\ProductionStageService::class)->initialize($manufacture, $data['stage_ids'] ?? null);
            return $manufacture;
        });

        return $manufacture;
    }

    public function update(Request $request, Store $store, $manufacture_id)
    {
        //

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'manufacture_start' => 'nullable|date',
            'manufacture_end' => 'nullable|date|after_or_equal:manufacture_start',
        ]);

        $manufacture = $store->manufactures()->findOrFail($manufacture_id);

        $manufacture->update($data);

        return $manufacture;
    }


}
