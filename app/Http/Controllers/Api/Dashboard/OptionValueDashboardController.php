<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Option;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Services\Dashboard\OptionValue\OptionValueDeletionService;

class OptionValueDashboardController extends Controller
{

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
        $request->merge(['value' => is_string($request->value) ? trim($request->value) : $request->value]);
        $validated = $request->validate([
            'option_id' => 'required|integer',
            'value'     => 'required|string|max:255',
        ]);

        $optionValue = DB::transaction(function () use ($store, $validated) {
            $option = Option::where('store_id', $store->id)
                ->whereNull('archived_at')
                ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
                ->lockForUpdate()->findOrFail($validated['option_id']);

            $normalized = mb_strtoupper($validated['value'], 'UTF-8');
            $duplicate = $option->optionValues()->get(['value'])->contains(
                fn ($existing) => mb_strtoupper(trim($existing->value), 'UTF-8') === $normalized
            );
            if ($duplicate) {
                throw ValidationException::withMessages(['value' => 'Este valor ya existe en esta opción.']);
            }

            return $option->optionValues()->create(['value' => $validated['value'], 'sort_order' => 1])->load('option');
        });

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
    public function destroy(Store $store, $product_id, $option_id, $option_value_id, OptionValueDeletionService $deletion)
    {
        return responseOk(
            $deletion->delete($store, (int) $product_id, (int) $option_id, (int) $option_value_id),
            'Valor y variantes asociadas eliminados correctamente'
        );
    }
}
