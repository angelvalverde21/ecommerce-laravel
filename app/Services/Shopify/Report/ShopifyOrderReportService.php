<?php

namespace App\Services\Shopify\Report;

use Illuminate\Support\Facades\Http;
use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShopifyOrderReportService extends ShopifyBaseService
{

    public function getOrderByName(string $orderName)
    {

        $fields = $this->buildOrderQuery();

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
            $query = $this->buildOrdersQuery(50, $endCursor, $startDate, $endDate);

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
}
