<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use Illuminate\Support\Facades\Log;

class ShopifyOrderService extends ShopifyBaseService
{

    public function getOrderByName(string $orderName)
    {

        $fields = $this->orderQuery(['withItem' => false, 'withCustomer' => false]);

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

        return $this->graphql($query);
    }

    public function getOrdersBetween($startDate, $endDate)
    {

        $orders = [];
        $hasNextPage = true;

        while ($hasNextPage) {

            $query = $this->ordersQuery(50, null, $startDate, $endDate);

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

    protected function mapOrder(array $order): array
    {
        return [
            'id' => $order['id'],
            'name' => $order['name'],
            'date' => $order['createdAt'],
            'total' => $order['totalPriceSet']['shopMoney']['amount'],
            'currency' => $order['totalPriceSet']['shopMoney']['currencyCode'],
            'items' => collect($order['lineItems']['edges'])->map(function ($edge) {
                $item = $edge['node'];
                return [
                    'title' => $item['variant']['product']['title'] ?? $item['name'],
                    'variant' => $item['variant']['title'] ?? null,
                    'price' => $item['variant']['price'] ?? null,
                    'quantity' => $item['quantity'],
                    'image' => $item['variant']['product']['featuredImage']['url'] ?? null,
                ];
            })->toArray(),
        ];
    }

    //orders
    public function getOrders($limit = 5, $cursor = null)
    {

        $response = $this->graphql($this->ordersQuery($limit, $cursor));

        Log::info($response);
        Log::info("ok");

        if ($response->failed()) {
            return ['error' => 'No se pudieron obtener las órdenes'];
        }

        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',  // ← Cambias 'products' por 'orders'
            ['lineItems', 'fulfillments', 'customer']  // ← Los campos anidados de órdenes
        );

        return [
            'orders'   => $result['items'],
            'pageInfo'   => $result['pageInfo'],
            'lastCursor' => $result['lastCursor'],
        ];

        // return $orders;

    }

}
