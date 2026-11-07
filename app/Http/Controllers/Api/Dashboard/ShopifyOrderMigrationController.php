<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Shopify\OrderImport\ShopifyOrderImportPlan;
use Illuminate\Http\Request;

class ShopifyOrderMigrationController extends Controller
{
    public function show(Store $store, Request $request, ShopifyOrderImportPlan $plan)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->where('stores.id', $store->id)->exists(), 403);

        return responseOk($plan->forStore($store));
    }
}
