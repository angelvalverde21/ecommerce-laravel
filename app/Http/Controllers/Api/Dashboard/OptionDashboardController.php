<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Store;
use App\Services\Dashboard\Option\OptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptionDashboardController extends Controller
{

    protected OptionService $optionService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->optionService = new OptionService();
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
    public function store(Store $store, int $product_id, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $option = $this->optionService->store(
            $store,
            $product_id,
            $validated['name']
        );

        //Todos los errores se manejan en CustomException y se convierten en respuestas json automaticamente
        return responseOk($option, 'Se creó correctamente');
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
