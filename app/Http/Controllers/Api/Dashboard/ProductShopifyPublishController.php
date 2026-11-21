<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Shopify\Catalog\CatalogPublisher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductShopifyPublishController extends Controller
{
    private function authorizeStore(Store $store, Request $request): int
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        return $user->id;
    }
    public function preview(Store $store, Request $request, CatalogPublisher $publisher)
    {
        $actor = $this->authorizeStore($store, $request);
        $data = $request->validate(['product_ids' => 'required|array|min:1|max:25', 'product_ids.*' => 'required|integer|distinct|min:1', 'mode' => ['sometimes', Rule::in(['catalog'])],
            'target_status' => ['required', Rule::in(['DRAFT'])]]);
        return responseOk($publisher->preview($store, $actor, array_map('intval', $data['product_ids']), $data['target_status']));
    }
    public function publish(Store $store, Request $request, int $product_id, CatalogPublisher $publisher)
    {
        $actor = $this->authorizeStore($store, $request);
        $data = $request->validate(['token' => 'required|string|max:12000']);
        return responseOk($publisher->publish($store, $actor, $product_id, $data['token']));
    }
}
