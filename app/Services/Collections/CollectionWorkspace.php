<?php
namespace App\Services\Collections;

use App\Models\{Manufacture, ManufactureVariant, Order, PhotoSession, Product, Collection, ProjectProcess, ProjectSale};
use App\Services\Dashboard\ProductionStageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollectionWorkspace
{
    public function ordersReady(): bool
    {
        return Schema::hasTable('orders') && Schema::hasColumns('orders', ['id', 'store_id', 'status', 'currency_code', 'variants']);
    }

    public function owner(ProjectProcess $process)
    {
        return match ($process->source_type) {
            'manufacture' => Manufacture::where('store_id', $process->store_id)->findOrFail($process->source_id),
            'photo_session' => PhotoSession::where('store_id', $process->store_id)->findOrFail($process->source_id),
            default => $process,
        };
    }

    public function received(int $storeId, int $manufactureId, ?int $variantId = null): int
    {
        return max(0, (int) DB::table('kardexes')->where('store_id', $storeId)
            ->whereIn('kardexable_type', [Manufacture::class, (new Manufacture)->getMorphClass()])->where('kardexable_id', $manufactureId)
            ->when($variantId !== null, fn ($q) => $q->where('variant_id', $variantId))
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END), 0) AS units")->value('units'));
    }

    public function lines(Order $order, ?\Illuminate\Support\Collection $items = null): array
    {
        if (in_array(strtolower($order->status), ['cancelled', 'canceled', 'cancelado', 'anulado', 'refunded'], true)) return [];
        if ($items === null && Schema::hasTable('order_items')) $items = DB::table('order_items')->where('order_id', $order->id)->get();
        if ($items?->isNotEmpty()) return $items->map(function ($item) {
                $payload = json_decode($item->source_payload ?? '{}', true) ?: [];
                return ['key' => 'item:'.$item->id, 'variant_id' => $item->variant_id, 'name' => $item->name,
                    'quantity' => max(0, (int) ($payload['currentQuantity'] ?? $item->quantity)),
                    'price' => $item->quantity > 0 ? (float) $item->line_total / $item->quantity : 0];
            })->all();
        return collect($order->variants ?? [])->filter(fn ($v) => !empty($v['id']))->map(fn ($v) => [
            'key' => 'variant:'.$v['id'], 'variant_id' => (int) $v['id'], 'name' => $v['name'] ?? $v['sku'] ?? 'Variante',
            'quantity' => max(0, (int) ($v['quantity'] ?? 0)), 'price' => (float) ($v['price'] ?? 0),
        ])->values()->all();
    }

    public function saleState(ProjectSale $sale): array
    {
        $order = $sale->order;
        $line = $order ? collect($this->lines($order))->firstWhere('key', $sale->line_key) : null;
        $valid = $line && (int) $line['variant_id'] === (int) $sale->variant_id && ($order->currency_code ?? 'PEN') === 'PEN';
        $allocated = (int) ProjectSale::where('order_id', $sale->order_id)->where('line_key', $sale->line_key)->sum('quantity');
        $quantity = $valid ? $sale->quantity * min(1, $line['quantity'] / max(1, $allocated)) : 0;
        return ['quantity' => $quantity, 'amount' => round($quantity * ($valid ? $line['price'] : 0), 2),
            'source_changed' => !$valid || $line['quantity'] < $allocated,
            'name' => $line['name'] ?? 'Línea modificada o anulada', 'order_name' => $order?->shopify_order_name ?: '#'.$sale->order_id];
    }

    public function overview(int $storeId, array $productIds, int $spentCents): array
    {
        $variantIds = DB::table('variants')->join('products', 'products.id', '=', 'variants.product_id')
            ->where('products.store_id', $storeId)->whereIn('products.id', $productIds)->pluck('variants.id')->all();
        $variants = array_fill_keys($variantIds, true);
        $salesCents = 0; $orderCount = 0;
        $ordersReady = $this->ordersReady();
        if (!$ordersReady && Schema::hasTable('orders') && DB::table('orders')->exists()) {
            // Unknown legacy rows must not be reported as zero sales or mixed across stores.
            return ['total_sales' => null, 'orders_count' => null, 'total_spent' => $spentCents / 100,
                'remaining_recovery' => null, 'recovery_percent' => null, 'recovery_status' => 'neutral', 'sales_available' => false];
        }
        $hasItems = Schema::hasTable('order_items');
        if ($variants && $ordersReady) Order::where('store_id', $storeId)->where(fn ($q) => $q->where('currency_code', 'PEN')->orWhereNull('currency_code'))
            ->select(['id', 'store_id', 'status', 'currency_code', 'variants'])
            ->chunkById(250, function ($orders) use ($variants, $hasItems, &$salesCents, &$orderCount) {
                $items = $hasItems ? DB::table('order_items')->whereIn('order_id', $orders->modelKeys())->get()->groupBy('order_id') : collect();
                foreach ($orders as $order) {
                    $matches = false;
                    foreach ($this->lines($order, $items->get($order->id, collect())) as $line) {
                        if (!isset($variants[$line['variant_id']]) || $line['quantity'] <= 0) continue;
                        $matches = true;
                        $salesCents += (int) round($line['price'] * $line['quantity'] * 100);
                    }
                    if ($matches) $orderCount++;
                }
            });
        $percent = $spentCents > 0 ? round(100 * $salesCents / $spentCents, 2) : null;
        $status = $spentCents <= 0 ? 'neutral' : ($salesCents * 2 < $spentCents ? 'low' : ($salesCents > $spentCents ? 'recovered' : 'progress'));
        return ['total_sales' => $salesCents / 100, 'orders_count' => $orderCount, 'total_spent' => $spentCents / 100,
            'remaining_recovery' => max(0, $spentCents - $salesCents) / 100, 'recovery_percent' => $percent, 'recovery_status' => $status, 'sales_available' => true];
    }

    public function allocate(ProjectProcess $process, Order $order, array $data, int $actor): ProjectSale
    {
        return DB::transaction(function () use ($process, $order, $data, $actor) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $process = ProjectProcess::whereKey($process->id)->lockForUpdate()->firstOrFail();
            $fail = fn ($message) => throw ValidationException::withMessages(['quantity' => $message]);
            if ($process->source_type !== 'manufacture' || (int) $order->store_id !== (int) $process->store_id) $fail('Selecciona una producción u orden de esta tienda.');
            if (($order->currency_code ?? 'PEN') !== 'PEN') $fail('El reporte trabaja en PEN. No se pueden mezclar monedas.');
            // Lock the stock source too: different order lines must not claim the same units concurrently.
            Manufacture::where('store_id', $process->store_id)->whereKey($process->source_id)->lockForUpdate()->firstOrFail();
            $line = collect($this->lines($order))->firstWhere('key', $data['line_key']);
            if (!$line || !$line['variant_id']) $fail('La línea ya no está disponible o no tiene una variante local identificada.');
            $already = ProjectSale::where('order_id', $order->id)->where('line_key', $line['key'])->sum('quantity');
            if ($already + $data['quantity'] > $line['quantity']) $fail('La cantidad supera las unidades pendientes de atribuir en esta venta.');
            $used = ProjectSale::where('process_id', $process->id)->where('variant_id', $line['variant_id'])->with('order')->get()
                ->sum(fn ($sale) => $this->saleState($sale)['quantity']);
            if ($used + $data['quantity'] > $this->received($process->store_id, $process->source_id, $line['variant_id'])) $fail('La cantidad supera las unidades recibidas de esta manufactura. Registra primero su recepción.');
            if (ProjectSale::where('process_id', $process->id)->where('order_id', $order->id)->where('line_key', $line['key'])->exists()) $fail('Esta línea ya está atribuida a esa manufactura. Retira la atribución anterior para corregirla.');
            return ProjectSale::create(['process_id' => $process->id, 'order_id' => $order->id, 'line_key' => $line['key'],
                'variant_id' => $line['variant_id'], 'quantity' => $data['quantity'], 'unit_price' => $line['price'], 'created_by' => $actor]);
        }, 3);
    }

    public function snapshot(Collection $collection): array
    {
        $collection->load('processes.responsible:id,name');
        $collections = Collection::where('store_id', $collection->store_id)->orderBy('name')->get(['id', 'name']);

        $selected = collect([$collection]);
        $processes = []; $purchases = []; $productIds = []; $events = []; $paidCents = 0; $costCents = 0;
        $planned = 0; $received = 0; $overdue = 0;
        $today = now('America/Lima')->toDateString();
        foreach ($selected as $collection) {
            foreach ($collection->processes as $process) {
                $owner = $this->owner($process);
                $stages = []; $status = $process->status; $progress = null; $processProductIds = [];
                if ($owner instanceof Manufacture) {
                    $sourcePlanned = (int) $owner->manufactureVariants()->sum('quantity');
                    $sourceReceived = $this->received($collection->store_id, $owner->id);
                    $planned += $sourcePlanned; $received += $sourceReceived;
                    if ($owner->type === 'order') {
                        $status = $sourcePlanned > 0 && $sourceReceived >= $sourcePlanned ? 'completed' : ($sourceReceived > 0 ? 'in_progress' : 'pending');
                        $progress = $sourcePlanned > 0 ? min(100, round(100 * $sourceReceived / $sourcePlanned)) : 0;
                    }
                    $processProductIds = DB::table('manufacture_variant')->join('variants', 'variants.id', '=', 'manufacture_variant.variant_id')
                        ->where('manufacture_id', $owner->id)->distinct()->pluck('variants.product_id')->all();
                    $productIds = array_merge($productIds, $processProductIds);
                    if ($owner->type === 'production') {
                        $enabled = app(ProductionStageService::class)->enabledIds($owner);
                        $stages = DB::table('production_stages')->whereIn('id', $enabled)->orderBy('position')->get();
                        $states = $owner->stageProgress()->whereIn('stage_id', $enabled)->pluck('status');
                        $done = $states->filter(fn ($s) => $s === 'completed')->count();
                        $progress = $enabled->count() ? round(100 * $done / $enabled->count()) : 0;
                        $status = $progress >= 100 ? 'completed' : ($states->contains('in_progress') || $done ? 'in_progress' : 'pending');
                    }
                    if ($owner->archived_at) $status = 'archived';
                } elseif ($owner instanceof PhotoSession) $status = $owner->status;
                $due = $process->due_on ?: ($owner instanceof PhotoSession ? $owner->getRawOriginal('delivery_date') : $owner->manufacture_end);
                if ($due && substr($due, 0, 10) < $today && !in_array($status, ['completed', 'archived', 'cancelled'])) $overdue++;
                $processPaid = 0; $processCost = 0;
                $rows = $owner->purchases()->where('store_id', $collection->store_id)->with(['items.unit', 'supplier.user', 'payments', 'deliveryTracking'])->withCount('attachments')->get();
                foreach ($rows as $purchase) {
                    $cents = $purchase->items->sum(fn ($item) => (int) round((float) $item->quantity * (float) $item->price * 100));
                    $costCents += $cents; $processCost += $cents;
                    $purchasePaid = 0;
                    foreach ($purchase->payments as $payment) {
                        if ($payment->status !== 'paid') continue;
                        $amount = (int) round((float) $payment->amount * 100) * ($payment->direction === 'out' ? 1 : -1);
                        $purchasePaid += $amount;
                        $events[] = ['date' => substr($payment->date ?: $payment->created_at, 0, 10), 'paid' => $amount, 'collected' => 0];
                    }
                    $paidCents += $purchasePaid; $processPaid += $purchasePaid;
                    $purchases[] = [...$purchase->toArray(), 'process_id' => $process->id, 'process_name' => $owner->name,
                        'collection_id' => $collection->id, 'collection_name' => $collection->name, 'amount' => $cents / 100, 'paid' => $purchasePaid / 100];
                }
                if ($owner instanceof Manufacture) foreach ($owner->payments()->where('status', 'paid')->get() as $payment) {
                    $amount = (int) round((float) $payment->amount * 100) * ($payment->direction === 'out' ? 1 : -1);
                    $paidCents += $amount; $processPaid += $amount;
                    $events[] = ['date' => substr($payment->date ?: $payment->created_at, 0, 10), 'paid' => $amount, 'collected' => 0];
                }
                $processes[] = [...$process->toArray(), 'name' => $owner->name, 'status' => $status, 'progress' => $progress, 'due_on' => $due ? substr($due, 0, 10) : null,
                    'collection_name' => $collection->name, 'purchases' => $rows->toArray(), 'purchases_amount' => $processCost / 100,
                    'paid' => $processPaid / 100, 'stages' => $stages, 'products_count' => count($processProductIds),
                    'owner_type' => match ($process->source_type) { 'manufacture' => 'manufacture', 'photo_session' => 'photo_session', default => 'project_process' },
                    'owner_id' => $owner->id];
            }
        }
        $processIds = array_column($processes, 'id');
        $sales = ProjectSale::whereIn('process_id', $processIds)->with(['order', 'receipts'])->get();
        $salesCents = 0; $collectedCents = 0; $unitsSold = 0; $salesRows = [];
        foreach ($sales as $sale) {
            $state = $this->saleState($sale); $salesCents += (int) round($state['amount'] * 100); $unitsSold += $state['quantity'];
            $net = 0;
            foreach ($sale->receipts as $receipt) {
                $amount = (int) round((float) $receipt->amount * 100) * ($receipt->direction === 'in' ? 1 : -1);
                $net += $amount; $events[] = ['date' => $receipt->date, 'paid' => 0, 'collected' => $amount];
            }
            $collectedCents += $net;
            $salesRows[] = [...$sale->toArray(), ...$state, 'collected' => $net / 100,
                'process_name' => collect($processes)->firstWhere('id', $sale->process_id)['name'] ?? ''];
        }
        $runningPaid = 0; $runningCollected = 0; $timeline = [];
        foreach (collect($events)->groupBy('date')->sortKeys() as $date => $day) {
            $runningPaid += $day->sum('paid'); $runningCollected += $day->sum('collected');
            $timeline[] = ['date' => $date, 'paid' => $runningPaid / 100, 'collected' => $runningCollected / 100];
        }
        $products = Product::where('store_id', $collection->store_id)->whereIn('id', array_unique($productIds))->with('image')->withStockTotal()->get();
        $manufactures = collect($processes)->where('source_type', 'manufacture')->keyBy('source_id');
        $variantRows = ManufactureVariant::whereIn('manufacture_id', $manufactures->keys())
            ->whereHas('variant', fn ($q) => $q->whereIn('product_id', $products->modelKeys()))
            ->with('variant.optionValues')->orderBy('id')->get()->groupBy(fn ($line) => $line->variant->product_id);
        foreach ($products as $product) {
            $lines = ($variantRows->get($product->id) ?? collect())->map(function ($line) use ($manufactures) {
                $process = $manufactures->get($line->manufacture_id);
                return ['id' => $line->id, 'variant_id' => $line->variant_id,
                    'name' => $line->variant->optionValues->pluck('value')->filter()->implode(' / ') ?: ($line->variant->title ?: 'Sin opciones'),
                    'sku' => $line->variant->sku, 'shopify_variant_id' => $line->variant->shopify_variant_id,
                    'quantity' => (int) $line->quantity,
                    'process_id' => $process['id'], 'source_id' => $line->manufacture_id,
                    'source_name' => $process['name'], 'source_type' => $process['kind']];
            })->values();
            $product->setAttribute('manufacture_variants', $lines);
            $product->setAttribute('manufacture_units', $lines->sum('quantity'));
        }
        return ['collection' => $collection, 'collections' => $collections->map(fn ($c) => $c->only(['id', 'name', 'description', 'launch_on']))->values(),
            'processes' => $processes, 'products' => $products, 'purchases' => $purchases, 'sales' => $salesRows, 'timeline' => $timeline,
            'overview' => $this->overview($collection->store_id, $products->modelKeys(), $costCents),
            'summary' => ['processes' => count($processes), 'completed' => collect($processes)->where('status', 'completed')->count(), 'overdue' => $overdue,
                'planned_units' => $planned, 'received_units' => $received, 'attributed_units' => $unitsSold,
                'purchases' => $costCents / 100, 'paid' => $paidCents / 100, 'pending_payment' => max(0, $costCents - $paidCents) / 100,
                'sales' => $salesCents / 100, 'collected' => $collectedCents / 100, 'cash_balance' => ($collectedCents - $paidCents) / 100,
                'remaining_recovery' => max(0, $paidCents - $collectedCents) / 100,
                'recovery_percent' => $paidCents > 0 ? round(100 * $collectedCents / $paidCents, 1) : null]];
    }
}
