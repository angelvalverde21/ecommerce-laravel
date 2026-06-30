<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class ManufactureKardexDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $manufacture_id)
    {
        //
        $kardexes = $store->manufactures()
            ->findOrFail($manufacture_id)
            ->kardexes()
            ->with(['variant.product.image', 'variant.optionValues'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn($item) => $item->created_at->format('Y-m-d'))
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'items' => $items->values(),
                ];
            })
            ->values();

        return responseOk($kardexes, "Listado de Kardexes obtenido correctamente");
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
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, Store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
