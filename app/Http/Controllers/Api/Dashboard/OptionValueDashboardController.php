<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Services\Dashboard\OptionValue\OptionValueService;

class OptionValueDashboardController extends Controller
{

    protected OptionValueService $optionValueService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->optionValueService = new OptionValueService();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $validated = $request->validate([
            'option_id' => 'required|integer',
            'value'     => 'required|string|max:255',
        ]);

        $optionValue = $this->optionValueService->store(
            $store,
            $validated['option_id'],
            $validated['value']
        );

        return responseOk(
            $optionValue,
            'Se agregó correctamente el optionValue'
        );
    }


    /**
     * Display the specified resource.
     */
    public function show(store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(store $store)
    {
        //
    }
}
