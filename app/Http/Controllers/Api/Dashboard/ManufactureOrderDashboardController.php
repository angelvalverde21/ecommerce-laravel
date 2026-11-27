<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Models\Manufacture;
use App\Models\Store;
use App\Services\Dashboard\Crud\ManufactureOrderService;
use Illuminate\Http\Request;

class ManufactureOrderDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $service;

    public function __construct(ManufactureOrderService $manufactureOrderService)
    {
        $this->service = $manufactureOrderService;
    }

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
    public function show(Store $store)
    {
        //
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
        return responseOk($this->service->update($request, $store, $manufacture_id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, Request $request, int $order_id)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        app(\App\Services\Dashboard\ProductionDeletionService::class)->deleteOrder($store, $order_id);
        return responseOk(['deleted' => true], 'Orden de compra eliminada correctamente.');
    }

    public function search(Store $store, SearchRequest $request)
    {
        if ($request->hasNoFilters()) {
            return $this->index($store);
        }

        $employees = $store->manufactures()->searchOrder($request->search)
            ->betweenDates($request->start_date, $request->end_date) //Sino hay fechas simplemente pasa de largo, no se considera
            ->withFinancialSummary()
            ->limit(10)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return responseOk(app(\App\Services\Collections\ManufactureCollectionLabels::class)->attach($employees, $store->id));
    }
}
