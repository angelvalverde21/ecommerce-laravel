<?php

namespace App\Services\Dashboard\Crud;

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
}
