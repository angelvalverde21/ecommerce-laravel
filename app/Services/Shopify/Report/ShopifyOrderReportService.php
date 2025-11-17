<?php

namespace App\Services\Shopify\Report;

use Illuminate\Support\Facades\Http;
use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use App\Services\Shopify\ShopifyOrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopifyOrderReportService extends ShopifyBaseService
{

    public function __construct(
        protected ShopifyOrderService $shopifyOrderService,
    ) {
        // Puedes inicializar algo aquí si lo necesitas
    }

    public function getOrderByName(string $orderName)
    {

        $fields = $this->orderQuery();

        $query = <<<GQL
        {
          orders(first: 1, query: "name:{$orderName}") {
            edges {
              node {
                {$fields}
              }
            }
          }
        }
        GQL;

        return $this->graphql($query)->json();
    }

    public function getOrdersBetween($startDate, $endDate)
    {

        $orders = [];
        $hasNextPage = true;
        $endCursor = null;

        while ($hasNextPage) {

            $after = $endCursor ? "\"{$endCursor}\"" : 'null';
            $query = $this->ordersQuery(50, $endCursor, $startDate, $endDate, []);

            $json = $this->graphql($query)->json();
            if (!isset($json['data']['orders']['edges'])) break;

            foreach ($json['data']['orders']['edges'] as $edge) {
                $orders[] = $this->mapOrder($edge['node']);
            }

            $pageInfo = $json['data']['orders']['pageInfo'] ?? [];
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $endCursor = $pageInfo['endCursor'] ?? null;

            usleep(500000); // throttle
        }

        return $orders;
    }

    // protected function mapOrder(array $order): array
    // {
    //     return [
    //         'id' => $order['id'],
    //         'name' => $order['name'],
    //         'date' => $order['createdAt'],
    //         'total' => $order['totalPriceSet']['shopMoney']['amount'],
    //         'currency' => $order['totalPriceSet']['shopMoney']['currencyCode'],
    //         'items' => collect($order['lineItems']['edges'])->map(function ($edge) {
    //             $item = $edge['node'];
    //             return [
    //                 'title' => $item['variant']['product']['title'] ?? $item['name'],
    //                 'variant' => $item['variant']['title'] ?? null,
    //                 'price' => $item['variant']['price'] ?? null,
    //                 'quantity' => $item['quantity'],
    //                 'image' => $item['variant']['product']['featuredImage']['url'] ?? null,
    //             ];
    //         })->toArray(),
    //     ];
    // }

    public function getOrdersAll()
    {
        $cacheKey = "shopify_orders_all";

        return Cache::remember($cacheKey, now()->addDay(), function () {

            $limit = 100;
            $cursor = null;
            $allOrders = [];

            do {
                $response = $this->shopifyOrderService->getOrders($limit, $cursor);

                if (isset($response['error'])) {
                    Log::error('Shopify error: ' . $response['error']);
                    break;
                }

                $orders = $response['orders'] ?? [];
                $pageInfo = $response['pageInfo'] ?? [];

                $allOrders = array_merge($allOrders, $orders);

                $hasNextPage = $pageInfo['hasNextPage'] ?? false;
                $cursor = $pageInfo['endCursor'] ?? ($response['lastCursor'] ?? null);

                Log::info("Fetched " . count($allOrders) . " orders so far...");

                // Previene rate limits (Shopify tiene límites de 2 req/s por app)
                if ($hasNextPage) {
                    usleep(500000); // 0.5s
                }
            } while ($hasNextPage);

            Log::info("Sync complete. Total orders fetched: " . count($allOrders));

            return [
                'count' => count($allOrders),
                'orders' => $allOrders,
                'cached_at' => now()->toDateTimeString(),
            ];
        });
    }

    public function getSalesReport()
    {
        $cacheKey = "shopify_orders_all";
        $cachedData = Cache::get($cacheKey);

        // 🔹 Si no hay caché, sincroniza todas las órdenes
        if (!$cachedData || empty($cachedData)) {
            Log::info("Cache no encontrado. Sincronizando órdenes de Shopify...");
            $cachedData = $this->shopifyOrderService->getAllOrders();

            // Guardar en caché por 1 hora (ajústalo si deseas)
            Cache::put($cacheKey, $cachedData, now()->addHour());
        }

        // 🔹 Validar que haya datos válidos
        if (isset($cachedData['error']) || empty($cachedData)) {
            return ['error' => 'No se pudieron obtener las órdenes desde Shopify.'];
        }

        // 🔹 Si ya vienen mapeadas, usamos directamente
        $orders = $cachedData;

        $report = [
            'total_sales' => 0,
            'years' => [],
        ];

        foreach ($orders as $order) {
            $date = Carbon::parse($order['created_at']);
            $year = $date->format('Y');
            $month = $date->format('m');
            $amount = floatval($order['total'] ?? 0);

            if (!isset($report['years'][$year])) {
                $report['years'][$year] = [
                    'total' => 0,
                    'months' => [],
                ];
            }

            if (!isset($report['years'][$year]['months'][$month])) {
                $report['years'][$year]['months'][$month] = 0;
            }

            $report['years'][$year]['months'][$month] += $amount;
            $report['years'][$year]['total'] += $amount;
            $report['total_sales'] += $amount;
        }

        return $report;
    }
}
