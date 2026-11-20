<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ImportShopifyRun;
use App\Models\Store;
use App\Services\Shopify\InitialImport\InitialImportService;
use App\Services\Shopify\InitialImport\InitialImportOrderBrowser;
use App\Services\Shopify\InitialImport\InitialImportOrderUpdates;
use App\Models\ImportShopifyOrderUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InitialShopifyImportController extends Controller
{
    private function authorizeStore(Store $store, Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
    }

    public function index(Store $store, Request $request, InitialImportService $service)
    {
        $this->authorizeStore($store, $request);
        $runs = Schema::hasTable('import_shopify_runs') ? ImportShopifyRun::where('store_id', $store->id)->latest()->limit(5)->get()->map(fn ($run) => $service->present($run))->all() : [];
        return responseOk(['checks' => $service->checks($store), 'runs' => $runs]);
    }

    public function store(Store $store, Request $request, InitialImportService $service)
    {
        $this->authorizeStore($store, $request);
        return responseOk($service->present($service->start($store, $request->user()->id)), 'Primera importación en cola.');
    }

    public function resume(Store $store, string $run_id, Request $request, InitialImportService $service)
    {
        $this->authorizeStore($store, $request);
        return responseOk($service->present($service->resume($store, $run_id)), 'Carga reanudada.');
    }

    public function orders(Store $store, Request $request, InitialImportOrderBrowser $browser)
    {
        $this->authorizeStore($store, $request);
        $filters = $request->validate(['run_id' => ['sometimes', 'uuid'], 'q' => ['sometimes', 'string', 'max:150'],
            'timeline' => ['sometimes', 'in:all,unavailable,with_comments,unchecked'],
            'work' => ['sometimes', 'in:all,ready,preparing,shipped,review,on_hold,scheduled,cancelled'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000']]);
        return responseOk($browser->index($store, $filters));
    }

    public function findNewOrders(Store $store, Request $request, InitialImportOrderUpdates $updates)
    {
        $this->authorizeStore($store, $request);
        $values = $request->validate(['run_id' => ['required', 'uuid']]);
        return responseOk($updates->present($updates->start($store, $values['run_id'], $request->user()->id)), 'Búsqueda de nuevos pedidos en cola.');
    }

    public function orderUpdate(Store $store, int $update_id, Request $request, InitialImportOrderUpdates $updates)
    {
        $this->authorizeStore($store, $request);
        $update = ImportShopifyOrderUpdate::whereHas('run', fn ($query) => $query->where('store_id', $store->id))->findOrFail($update_id);
        return responseOk($updates->present($update));
    }

    public function order(Store $store, int $order_id, Request $request, InitialImportOrderBrowser $browser)
    {
        $this->authorizeStore($store, $request);
        return responseOk($browser->show($store, $order_id));
    }

    public function events(Store $store, int $order_id, Request $request, InitialImportOrderBrowser $browser)
    {
        $this->authorizeStore($store, $request);
        $filters = $request->validate(['type' => ['sometimes', 'in:all,comments,system'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000']]);
        return responseOk($browser->events($store, $order_id, $filters));
    }

    public function attachment(Store $store, int $order_id, int $attachment_id, Request $request, InitialImportOrderBrowser $browser)
    {
        $this->authorizeStore($store, $request);
        $order = $browser->order($store, $order_id);
        $file = DB::table('import_shopify_event_attachments')->where('id', $attachment_id)->where('run_id', $order->run_id)->where('order_shopify_id', $order->shopify_id)->firstOrFail();
        abort_unless($file->status === 'downloaded' && $file->disk === 'local' && preg_match('/^[a-f0-9]{64}$/', $file->task_key), 404);
        $relative = 'import-shopify/'.$order->run_id.'/attachments/'.$file->task_key.'.original';
        abort_unless($file->local_path === $relative && is_file(storage_path('app/private/'.$relative)), 404);
        $payload = json_decode($file->payload, true, 512, JSON_THROW_ON_ERROR);
        $name = preg_replace('/[\r\n]/', '', basename(str_replace('\\', '/', $payload['name'] ?? 'adjunto')));
        return response()->download(storage_path('app/private/'.$relative), $name ?: 'adjunto', [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
