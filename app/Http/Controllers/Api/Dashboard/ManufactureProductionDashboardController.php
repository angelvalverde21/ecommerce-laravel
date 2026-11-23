<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Models\Manufacture;
use App\Models\Store;
use App\Services\Dashboard\Crud\ManufactureProductionService;
use Illuminate\Http\Request;

class ManufactureProductionDashboardController extends Controller
{
    protected $service;

    public function __construct(ManufactureProductionService $manufactureProductionService)
    {
        $this->service = $manufactureProductionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        return responseOk($this->service->index($store));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, Request $request)
    {
        //
        return responseOk($this->service->store($store, $request));
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $manufacture_id)
    {
        $manufacture = $this->service->show($store, $manufacture_id);

        if (!$manufacture) {
            return responseError("Error al obtener la producción");
        }

        return responseOk($manufacture, 'Producción obtenida correctamente');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Store $store, Request $request, $manufacture_id)
    {
        //
        return responseOk($this->service->update($request, $store, $manufacture_id), "Manufactures Production actualizado correctamente");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, Request $request, int $production_id)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        app(\App\Services\Dashboard\ProductionDeletionService::class)->delete($store, $production_id);
        return responseOk(['deleted' => true], 'Producción eliminada correctamente.');
    }

    public function archive(Store $store, Request $request, int $production_id)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        $data = $request->validate(['archived' => 'required|boolean']);
        $production = \Illuminate\Support\Facades\DB::transaction(function () use ($store, $production_id, $data) {
            $production = $store->manufactures()->where('type', 'production')->lockForUpdate()->findOrFail($production_id);
            $production->update(['archived_at' => $data['archived'] ? ($production->archived_at ?? now()) : null]);
            return $production;
        });
        return responseOk($production, $data['archived'] ? 'Producción archivada.' : 'Producción restaurada.');
    }


    public function search(Store $store, SearchRequest $request)
    {
        if ($request->hasNoFilters()) {
            return $this->index($store);
        }

        return responseOk($this->service->index($store, $request));
    }
}
