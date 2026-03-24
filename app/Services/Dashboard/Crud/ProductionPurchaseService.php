<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionPurchaseService
{

    // Compras de produccion

    public function index(Store $store, $production_id)
    {

        try {

            Log::info($production_id);

            $purchases = $store->productions()
            ->findOrFail($production_id)
            ->purchases()->with(['supplier', 'items'])
            ->orderBy('id', 'desc')
            ->get();


            Log::info($purchases);

            return $purchases;

        } catch (\Throwable $th) {

            Log::info($th);

            return null;
        }
    }
}
