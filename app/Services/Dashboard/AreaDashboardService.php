<?php

namespace App\Services\Dashboard;

use App\Models\Product;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AreaDashboardService
{
    public function report(Store $store, string $area, int $days): array
    {
        $now = CarbonImmutable::now('America/Lima');
        $from = $now->startOfDay()->subDays($days - 1);
        $sections = [];
        $orderColumns = Schema::getColumnListing('orders');
        $ordersReady = count(array_diff(['store_id', 'total', 'created_at'], $orderColumns)) === 0;
        $statusReady = in_array('status', $orderColumns, true);

        if (in_array($area, ['sales', 'ceo'], true)) {
            $sections[] = $this->sales($store, $from, $now, $days, $ordersReady, $statusReady);
        }
        if (in_array($area, ['packing', 'ceo'], true)) {
            $sections[] = $this->packing($store, $from, $now, $ordersReady && $statusReady);
        }
        if (in_array($area, ['marketing', 'ceo'], true)) {
            $sections[] = $this->marketing($store, $from, $now);
        }

        $report = [
            'area' => $area,
            'store' => $store->only(['id', 'name', 'slug']),
            'range' => ['days' => $days, 'from' => $from->toDateString(), 'to' => $now->toDateString(), 'timezone' => 'America/Lima'],
            'updated_at' => $now->toIso8601String(),
            'sections' => $sections,
        ];
        if ($area === 'ceo') {
            $report['production_progress'] = app(ProductionProgressService::class)->forStore($store, $from, $now);
        }

        return $report;
    }

    private function orders(Store $store, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return DB::table('orders')->where('store_id', $store->id)->whereBetween('created_at', [$from, $to]);
    }

    private function metric(string $label, int|float|null $value, string $format = 'number', string $hint = ''): array
    {
        return compact('label', 'value', 'format', 'hint');
    }

    private function sales(Store $store, CarbonImmutable $from, CarbonImmutable $to, int $days, bool $ready, bool $statusReady): array
    {
        $section = [
            'key' => 'sales', 'title' => 'Ventas', 'description' => 'Actividad comercial y evolución de los pedidos.',
            'available' => $ready, 'metrics' => [], 'trend' => [], 'items' => [],
            'items_title' => 'Pedidos recientes', 'actions' => [],
            'note' => 'Importes de pedidos locales registrados; no equivalen a cobros confirmados.',
        ];
        if (! $ready) {
            $section['metrics'] = [
                $this->metric('Pedidos registrados', null), $this->metric('Importe de pedidos', null, 'money'),
                $this->metric('Ticket promedio', null, 'money'), $this->metric('Pedidos cancelados', null),
            ];
            $section['note'] = 'Los indicadores de ventas estarán disponibles al completar la configuración de pedidos locales.';

            return $section;
        }

        $orders = $this->orders($store, $from, $to);
        $valid = clone $orders;
        if ($statusReady) {
            $valid->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'cancelado');
            });
        }
        $count = (clone $valid)->count();
        $amount = round((float) (clone $valid)->sum('total'), 2);
        $section['metrics'] = [
            $this->metric('Pedidos registrados', (clone $orders)->count()),
            $this->metric('Importe de pedidos', $amount, 'money', 'Excluye cancelaciones'),
            $this->metric('Ticket promedio', $count ? round($amount / $count, 2) : 0, 'money'),
            $this->metric('Pedidos cancelados', $statusReady ? (clone $orders)->where('status', 'cancelado')->count() : null),
        ];
        $daily = (clone $valid)->selectRaw('DATE(created_at) AS day, SUM(total) AS amount')->groupByRaw('DATE(created_at)')->pluck('amount', 'day');
        for ($index = 0; $index < $days; $index++) {
            $date = $from->addDays($index);
            $section['trend'][] = ['date' => $date->toDateString(), 'value' => round((float) ($daily[$date->toDateString()] ?? 0), 2)];
        }
        $section['trend_format'] = 'money';
        $section['items'] = (clone $orders)->orderByDesc('created_at')->orderByDesc('id')->limit(6)->get()->map(function ($order) use ($statusReady) {
            return [
                'id' => $order->id, 'title' => 'Pedido #'.$order->id, 'subtitle' => $order->created_at,
                'status' => $statusReady ? $order->status : null, 'value' => (float) $order->total,
                'path' => 'orders/'.$order->id,
            ];
        })->all();
        $section['actions'] = [['label' => 'Crear pedido', 'path' => 'orders/create'], ['label' => 'Ver pedidos', 'path' => 'orders']];

        return $section;
    }

    private function packing(Store $store, CarbonImmutable $from, CarbonImmutable $to, bool $ready): array
    {
        $section = [
            'key' => 'packing', 'title' => 'Empaque', 'description' => 'Prioridades de preparación y salida de pedidos.',
            'available' => $ready, 'items_title' => 'Cola de preparación', 'items' => [], 'actions' => [],
            'note' => 'La cola incluye todos los pedidos por preparar. Enviados: pedidos creados en el periodo que tienen estado enviado.',
        ];
        if (! $ready) {
            $section['metrics'] = [
                $this->metric('Por preparar', null), $this->metric('En preparación', null),
                $this->metric('Enviados en el periodo', null), $this->metric('En espera', null),
            ];
            $section['note'] = 'El seguimiento de empaque estará disponible cuando se habiliten los estados operativos de los pedidos.';

            return $section;
        }
        $all = DB::table('orders')->where('store_id', $store->id)->where('created_at', '<=', $to);
        $section['metrics'] = [
            $this->metric('Por preparar', (clone $all)->where('status', 'pago_confirmado')->count()),
            $this->metric('En preparación', (clone $all)->where('status', 'preparacion')->count()),
            $this->metric('Enviados en el periodo', $this->orders($store, $from, $to)->where('status', 'enviado')->count()),
            $this->metric('En espera', (clone $all)->where('status', 'en_espera')->count()),
        ];
        $section['items'] = (clone $all)->whereIn('status', ['pago_confirmado', 'preparacion'])
            ->orderBy('created_at')->orderBy('id')->limit(8)->get()->map(function ($order) {
                return [
                    'id' => $order->id, 'title' => 'Pedido #'.$order->id, 'subtitle' => $order->created_at,
                    'status' => $order->status, 'value' => null, 'path' => 'orders/'.$order->id,
                ];
            })->all();
        $section['actions'] = [['label' => 'Ir a pedidos', 'path' => 'orders']];

        return $section;
    }

    private function marketing(Store $store, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $catalog = Product::where('store_id', $store->id)->where('status', 1);
        $ready = (clone $catalog)->whereHas('images')->whereHas('prices')->count();
        $products = (clone $catalog)->where(function ($query) {
            $query->whereDoesntHave('images')->orWhereDoesntHave('prices');
        })->withCount(['images', 'prices'])->orderByDesc('created_at')->orderByDesc('id')->limit(6)->get();

        return [
            'key' => 'marketing', 'title' => 'Marketing', 'description' => 'Catálogo y material disponible para preparar contenido.',
            'available' => true,
            'metrics' => [
                $this->metric('Productos activos', (clone $catalog)->count()),
                $this->metric('Con imágenes y precios', $ready),
                $this->metric('Sin imágenes', (clone $catalog)->whereDoesntHave('images')->count()),
                $this->metric('Nuevos en el periodo', (clone $catalog)->whereBetween('created_at', [$from, $to])->count()),
            ],
            'items_title' => 'Productos por completar',
            'items' => $products->map(function ($product) {
                $missing = [];
                if (! $product->images_count) {
                    $missing[] = 'Agregar imágenes';
                }
                if (! $product->prices_count) {
                    $missing[] = 'Completar precios';
                }

                return [
                    'id' => $product->id, 'title' => $product->name, 'subtitle' => implode(' · ', $missing),
                    'status' => null, 'value' => null, 'path' => 'products/'.$product->id,
                ];
            })->all(),
            'actions' => [['label' => 'Ver catálogo', 'path' => 'products']],
            'note' => 'El catálogo refleja su estado actual. Las métricas de alcance, inversión y campañas se incorporarán al conectar esas fuentes.',
        ];
    }
}
