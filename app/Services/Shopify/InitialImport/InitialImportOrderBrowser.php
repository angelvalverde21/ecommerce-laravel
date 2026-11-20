<?php

namespace App\Services\Shopify\InitialImport;

use App\Models\ImportShopifyRun;
use App\Models\Store;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Read-only views of the original import, scoped to its store and execution. */
class InitialImportOrderBrowser
{
    public function index(Store $store, array $filters): array
    {
        $runs = ImportShopifyRun::where('store_id', $store->id)->latest()->limit(50)->get(['id', 'created_at', 'status']);
        $run = isset($filters['run_id']) ? ImportShopifyRun::where('store_id', $store->id)->findOrFail($filters['run_id']) : $runs->first();
        if (!$run) return ['run' => null, 'runs' => [], 'summary' => ['total' => 0, 'unavailable' => 0, 'with_comments' => 0, 'unchecked' => 0], 'packing_summary' => ['total' => 0, 'ready' => 0, 'preparing' => 0, 'shipped' => 0], 'items' => [], 'pagination' => ['page' => 1, 'last_page' => 1, 'total' => 0]];

        $base = DB::table('import_shopify_orders as orders')->leftJoin('import_shopify_tasks as timeline', function ($join) {
            $join->on('timeline.run_id', '=', 'orders.run_id')->on('timeline.owner_id', '=', 'orders.shopify_id')->where('timeline.kind', 'events');
        })->where('orders.run_id', $run->id);
        $summary = $this->counts($base);
        $packing = app(InitialImportPacking::class);
        $packingSummary = $packing->counts(DB::table('import_shopify_orders as orders')->where('orders.run_id', $run->id));
        $query = clone $base;
        if (($filters['work'] ?? 'all') !== 'all') $query->whereRaw('('.$packing->expression().') = ?', [$filters['work']]);
        match ($filters['timeline'] ?? 'all') {
            'unavailable' => $query->where('timeline.metadata->availability', 'unavailable_comments'),
            'with_comments' => $query->where('timeline.metadata->comment_count', '>', 0),
            'unchecked' => $query->where(function ($query) { $query->whereNull('timeline.status')->orWhere('timeline.status', '!=', 'completed')->orWhereNull('timeline.metadata->availability')->orWhere('timeline.metadata->availability', 'unknown'); }),
            default => null,
        };
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(function ($query) use ($search) {
                foreach (['name', 'email', 'phone', 'customer->displayName', 'customer->email'] as $field) $query->orWhere('orders.payload->'.$field, 'like', '%'.$search.'%');
                $query->orWhere('orders.shopify_id', 'like', '%'.$search.'%');
            });
        }
        $page = $query->select('orders.*', 'timeline.metadata as timeline_metadata', 'timeline.status as timeline_status')
            ->selectRaw($packing->expression().' AS packing_state')
            ->selectSub($this->eventCount($run->id), 'events_count')
            ->orderByDesc('orders.payload->createdAt')->orderByDesc('orders.id')->paginate(25, ['*'], 'page', $filters['page'] ?? 1);
        return ['run' => $run->only(['id', 'created_at', 'status']), 'runs' => $runs->toArray(), 'summary' => $summary, 'packing_summary' => $packingSummary,
            'order_update' => app(InitialImportOrderUpdates::class)->latest($run),
            'items' => $this->packingRows($run->id, $page->items()),
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]];
    }

    private function packingRows(string $runId, array $rows): array
    {
        // Enrich the current page in batches; never query images once per order.
        $records = DB::table('import_shopify_order_items')->where('run_id', $runId)
            ->whereIn('parent_shopify_id', array_column($rows, 'shopify_id'))->orderBy('id')->get();
        $images = app(InitialImportOrderItemImages::class)->enrich($runId, $records->map(fn ($record) => $this->payload($record))->all());
        $items = [];
        foreach ($records as $index => $record) $items[$record->parent_shopify_id][] = $images[$index];

        return array_map(function ($row) use ($items) {
            $data = $this->payload($row);
            $lines = array_values(array_filter($items[$row->shopify_id] ?? [], fn ($item) => ($item['currentQuantity'] ?? $item['quantity'] ?? 0) > 0));
            $destination = implode(', ', array_unique(array_filter([$data['shippingAddress']['city'] ?? null, $data['shippingAddress']['province'] ?? null])));
            return [...$this->summary($row), 'packing_state' => $row->packing_state,
                'destination' => $destination ?: (($data['sourceName'] ?? '') === 'pos' ? 'Venta en tienda' : null),
                'item_count' => count($lines), 'units_count' => array_sum(array_map(fn ($item) => $item['currentQuantity'] ?? $item['quantity'] ?? 0, $lines)),
                'packing_items' => array_slice($lines, 0, 3)];
        }, $rows);
    }

    public function order(Store $store, int $id): object
    {
        return DB::table('import_shopify_orders')->where('id', $id)->whereExists(function ($query) use ($store) {
            $query->selectRaw('1')->from('import_shopify_runs')->whereColumn('import_shopify_runs.id', 'import_shopify_orders.run_id')->where('store_id', $store->id);
        })->firstOrFail();
    }

    public function show(Store $store, int $id): array
    {
        $row = $this->order($store, $id);
        $timeline = DB::table('import_shopify_tasks')->where('run_id', $row->run_id)->where('kind', 'events')->where('owner_id', $row->shopify_id)->first();
        $row->timeline_metadata = $timeline?->metadata;
        $row->timeline_status = $timeline?->status;
        $events = $this->eventsQuery($row);
        $row->events_count = (clone $events)->count();
        $related = [];
        foreach (['shipping_lines', 'discounts', 'transactions', 'fulfillments', 'refunds', 'addresses', 'metafields'] as $entity) {
            $related[$entity] = $this->children($row, $entity);
        }
        foreach (['fulfillment_items' => 'fulfillments', 'fulfillment_events' => 'fulfillments', 'refund_items' => 'refunds', 'refund_shipping_lines' => 'refunds', 'refund_adjustments' => 'refunds'] as $entity => $parents) {
            $related[$entity] = DB::table('import_shopify_'.$entity)->where('run_id', $row->run_id)->whereIn('parent_shopify_id', array_column($related[$parents], 'id'))->orderBy('id')->get()->map(fn ($record) => $this->payload($record))->all();
        }
        $run = ImportShopifyRun::findOrFail($row->run_id);
        return ['order' => [...$this->summary($row), 'payload' => $this->payload($row)],
            'run' => $run->only(['id', 'created_at', 'status']),
            'items' => app(InitialImportOrderItemImages::class)->enrich($row->run_id, $this->children($row, 'order_items')), 'related' => $related,
            'event_counts' => ['all' => $row->events_count, 'comments' => (clone $events)->where('payload->__typename', 'CommentEvent')->count(), 'system' => (clone $events)->where('payload->__typename', 'BasicEvent')->count()],
            'step_events' => (clone $events)->where('payload->__typename', 'BasicEvent')
                ->whereIn('payload->action', ['sale_success', 'capture_success', 'fulfillment_pending', 'fulfillment_success'])
                ->get(['payload'])->map(fn ($event) => array_intersect_key($this->payload($event), array_flip(['action', 'createdAt'])))->all(),
            'shopify_url' => 'https://'.$run->shop_domain.'/admin/orders/'.rawurlencode(basename($row->shopify_id))];
    }

    public function events(Store $store, int $id, array $filters): array
    {
        $order = $this->order($store, $id);
        $query = $this->eventsQuery($order);
        if (($filters['type'] ?? 'all') !== 'all') $query->where('payload->__typename', $filters['type'] === 'comments' ? 'CommentEvent' : 'BasicEvent');
        $page = $query->orderBy('payload->createdAt')->orderBy('id')->paginate(25, ['*'], 'page', $filters['page'] ?? 1);
        $attachments = DB::table('import_shopify_event_attachments')->where('run_id', $order->run_id)->where('order_shopify_id', $order->shopify_id)
            ->whereIn('parent_shopify_id', collect($page->items())->pluck('shopify_id'))->orderBy('id')->get()->groupBy('parent_shopify_id');
        return ['items' => collect($page->items())->map(function ($row) use ($attachments) {
            $payload = $this->payload($row);
            return ['id' => $row->id, 'shopify_id' => $row->shopify_id, 'kind' => $payload['__typename'] ?? 'BasicEvent', 'created_at' => $payload['createdAt'] ?? null,
                'message' => trim(html_entity_decode(strip_tags(preg_replace('~<(?:br\s*/?|/p)>~i', "\n", $payload['message'] ?? $payload['rawMessage'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'edited' => (bool) ($payload['edited'] ?? false), 'critical' => (bool) ($payload['criticalAlert'] ?? false),
                'attachments' => ($attachments->get($row->shopify_id) ?? collect())->map(fn ($file) => $this->attachment($file))->values()->all(), 'payload' => $payload];
        })->all(), 'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]];
    }

    private function summary(object $row): array
    {
        $data = $this->payload($row);
        $audit = json_decode($row->timeline_metadata ?? '{}', true) ?? [];
        $status = ($row->timeline_status ?? null) !== 'completed' ? 'unchecked' : ($audit['availability'] ?? 'unknown');
        return ['id' => $row->id, 'run_id' => $row->run_id, 'shopify_id' => $row->shopify_id, 'name' => $data['name'] ?? basename($row->shopify_id),
            'created_at' => $data['createdAt'] ?? null, 'customer' => $data['customer']['displayName'] ?? $data['shippingAddress']['name'] ?? 'Sin cliente',
            'email' => $data['email'] ?? $data['customer']['email'] ?? null, 'financial_status' => $data['displayFinancialStatus'] ?? null,
            'fulfillment_status' => $data['displayFulfillmentStatus'] ?? null, 'cancelled_at' => $data['cancelledAt'] ?? null,
            'total' => $data['currentTotalPriceSet']['shopMoney'] ?? $data['totalPriceSet']['shopMoney'] ?? null,
            'timeline_status' => $status, 'comment_count' => $audit['comment_count'] ?? 0, 'events_count' => (int) ($row->events_count ?? 0)];
    }

    private function counts(Builder $query): array
    {
        $grammar = DB::connection()->getQueryGrammar();
        $availability = $grammar->wrap('timeline.metadata->availability');
        $comments = $grammar->wrap('timeline.metadata->comment_count');
        $counts = (clone $query)->selectRaw("COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN $availability = ? THEN 1 ELSE 0 END), 0) AS unavailable,
            COALESCE(SUM(CASE WHEN $comments > 0 THEN 1 ELSE 0 END), 0) AS with_comments,
            COALESCE(SUM(CASE WHEN timeline.status IS NULL OR timeline.status != ? OR $availability IS NULL OR $availability = ? THEN 1 ELSE 0 END), 0) AS unchecked",
            ['unavailable_comments', 'completed', 'unknown'])->first();
        return array_map('intval', (array) $counts);
    }

    private function eventsQuery(object $order): Builder
    {
        return DB::table('import_shopify_events')->where('run_id', $order->run_id)->where('parent_shopify_id', $order->shopify_id);
    }

    private function eventCount(string $run): Builder
    {
        return DB::table('import_shopify_events')->selectRaw('COUNT(*)')->where('run_id', $run)->whereColumn('parent_shopify_id', 'orders.shopify_id');
    }

    private function children(object $order, string $entity): array
    {
        return DB::table('import_shopify_'.$entity)->where('run_id', $order->run_id)->where('parent_shopify_id', $order->shopify_id)->orderBy('id')->get()->map(fn ($record) => $this->payload($record))->all();
    }

    private function payload(object $row): array { return json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR); }

    private function attachment(object $row): array
    {
        $data = $this->payload($row);
        $url = $data['url'] ?? null;
        $host = strtolower(parse_url($url ?? '', PHP_URL_HOST) ?? '');
        $safe = parse_url($url ?? '', PHP_URL_SCHEME) === 'https' && ($host === 'cdn.shopify.com' || $host === 'shopify-private-shop-assets.storage.googleapis.com' || str_ends_with($host, '.shopifycdn.com'));
        return ['id' => $row->id, 'name' => $data['name'] ?? 'Archivo adjunto', 'status' => $row->status,
            'is_image' => in_array($row->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/bmp'], true),
            'bytes' => $row->bytes, 'remote_url' => $safe ? $url : null];
    }
}
