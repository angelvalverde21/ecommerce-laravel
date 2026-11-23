<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{Collection, Store};
use App\Services\Shopify\Catalog\CollectionInventoryExporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CollectionShopifyInventoryController extends Controller
{
    private function collection(Store $store, Request $request, int $id): Collection
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        return Collection::where('store_id', $store->id)->findOrFail($id);
    }

    public function preview(Store $store, Request $request, int $collection_id, CollectionInventoryExporter $exporter)
    {
        $collection = $this->collection($store, $request, $collection_id);
        $data = $request->validate(['variant_ids' => 'required|array|min:1|max:250', 'variant_ids.*' => 'required|integer|distinct|min:1',
            'percentage' => ['required', 'integer', Rule::in([20, 30, 40, 50, 60, 70, 80, 90, 100])]]);
        return $this->respond(fn () => $exporter->preview($collection, (int) $request->user()->id, array_map('intval', $data['variant_ids']), (int) $data['percentage']));
    }

    public function send(Store $store, Request $request, int $collection_id, CollectionInventoryExporter $exporter)
    {
        $collection = $this->collection($store, $request, $collection_id);
        $data = $request->validate(['token' => 'required|string|max:300000']);
        return $this->respond(fn () => $exporter->send($collection, (int) $request->user()->id, $data['token']));
    }

    private function respond(callable $action)
    {
        try { return responseOk($action()); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(422, 'Shopify no confirmó la conexión. Reintenta el mismo envío para comprobar su resultado.'); }
        catch (\RuntimeException $error) {
            if ($error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) throw $error;
            abort(422, $error->getMessage());
        }
    }
}
