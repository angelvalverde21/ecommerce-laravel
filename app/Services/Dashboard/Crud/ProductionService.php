<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Production;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionService
{
    public function store(Store $store, Request $request)
    {
        //

        try {

            DB::beginTransaction();

            $data = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $production = $store->productions()->create(
                [
                    'name' => $data['name'],
                    'type' => 'production',
                    'user_id' => Auth::id(),
                ]
            );

            DB::commit();

            return $production;
        } catch (\Throwable $th) {

            Log::info($th);

            DB::rollback();

            return null;
        }
    }

    public function show(Store $store, $production_id): Production
    {
        return $store->productions()
            ->withFinancialSummary()
            ->findOrFail($production_id);
    }

    public function index(Store $store)
    {
        return $store->productions()
            ->withFinancialSummary()
            ->with('user')
            ->get();
    }

    public function update(Store $store, $production_id, Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'budget' => 'nullable|numeric',
            'quantity_total' => 'nullable|integer',
        ]);

        $production = $store->productions()->findOrFail($production_id);
        $production->update($data);

        return $production->fresh();
    }
}
