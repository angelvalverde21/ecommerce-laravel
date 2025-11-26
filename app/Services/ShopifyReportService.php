<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Helpers\GraphQLResponseHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShopifyReportService
{
  protected string $apiUrl;
  protected string $token;
  protected string $pageInfo;

  public function __construct()
  {
    $this->apiUrl = "https://" . config('shopify.store') . ".myshopify.com/admin/api/" . config('shopify.version') . "/graphql.json";
    $this->token = config('shopify.token');

    $this->pageInfo = '
                pageInfo {
                  hasNextPage
                  endCursor
                }
              ';
  }

  /**
   *  Query base reutilizable (solo define los campos)
   */
  protected function getOrderFields(): string
  {
    return <<<GQL
            id
            name
            createdAt
            totalPriceSet {
                shopMoney {
                    amount
                    currencyCode
                }
            }
            lineItems(first: 50) {
                edges {
                    node {
                        name
                        quantity
                        variant {
                            id
                            title
                            price
                            image { url }
                            product {
                                title
                                featuredImage { url }
                            }
                        }
                    }
                }
            }
        GQL;
  }

  public function getOrderByName(string $orderName): ?array
  {
    $fields = $this->getOrderFields();

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

    $data = responseShopify($query);

    return $data['data']['orders']['edges'][0]['node'] ?? null;
  }

  public function topSellingProducts(string $fromDate, string $toDate, int $limit = 250)
  {
    // Filtro por rango de fechas (ISO8601 UTC)
    $queryFilter = "financial_status:PAID created_at:>={$fromDate} created_at:<={$toDate}";

    $query = '
        {
          orders(first: ' . $limit . ', query: "' . $queryFilter . '", sortKey: CREATED_AT, reverse: true) {
            edges {
              node {
                id
                createdAt
                lineItems(first: 250) {
                  edges {
                    node {
                      quantity
                      name
                      originalUnitPriceSet {
                        shopMoney {
                          amount
                          currencyCode
                        }
                      }
                      variant {
                        id
                        title
                        image {
                          originalSrc
                        }
                        product {
                          id
                          title
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }';

    // $response = Http::withHeaders([
    //   'Content-Type' => 'application/json',
    //   'X-Shopify-Access-Token' => $this->token,
    // ])->post($this->apiUrl, ['query' => $query]);

    $data = responseShopify($query);

    // return $data;

    // Procesar los resultados
    $items = collect($data['data']['orders']['edges'] ?? [])
      ->flatMap(fn($edge) => $edge['node']['lineItems']['edges'])
      ->map(fn($item) => [
        'product_id' => $item['node']['variant']['product']['id'] ?? null,
        'product_title' => $item['node']['variant']['product']['title'] ?? null,
        'variant_title' => $item['node']['variant']['title'] ?? null,
        'variant_image' => $item['node']['variant']['image']['originalSrc'] ?? null,
        'quantity' => (int) $item['node']['quantity'],
        'price' => (float) $item['node']['originalUnitPriceSet']['shopMoney']['amount'],
        'currency' => $item['node']['originalUnitPriceSet']['shopMoney']['currencyCode'] ?? 'PEN'
      ])
      ->groupBy('product_id')
      ->map(function ($group) {
        $currency = $group->first()['currency'];

        $totalQty = $group->sum('quantity');
        $totalAmount = $group->sum(fn($i) => $i['quantity'] * $i['price']);

        return [
          'product_title' => $group->first()['product_title'],
          'total_sold' => $totalQty,
          'total_amount' => round($totalAmount, 2),
          'currency' => $currency,
          'variants' => $group->map(fn($i) => [
            'title' => $i['variant_title'],
            'image' => $i['variant_image'],
            'quantity' => $i['quantity'],
            'subtotal' => round($i['quantity'] * $i['price'], 2)
          ])->values()
        ];
      })
      ->sortByDesc('total_sold')
      ->values();

    return $items->take(50)->toArray(); // top 20 productos
  }

  public function getOrdersBetween($startDate, $endDate)
  {
    $orders = [];
    $hasNextPage = true;
    $endCursor = null;

    while ($hasNextPage) {
      $query = <<<GRAPHQL
            {
              orders(
                first: 50,
                sortKey: CREATED_AT,
                query: "financial_status:paid cancelled_at:null fulfillment_status:unfulfilled created_at:>={$startDate} created_at:<={$endDate}",
                after: %s
              ) {
                {$this->pageInfo}
                edges {
                  node {
                    id
                    name
                    createdAt
                    totalPriceSet { shopMoney { amount currencyCode } }
                    lineItems(first: 50) {
                      edges {
                        node {
                          name
                          quantity
                          variant {
                            id
                            title
                            price
                            image { url }
                            product { 
                            title
                            createdAt
                            featuredImage {
                              url
                            }
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
            GRAPHQL;

      $after = $endCursor ? "\"{$endCursor}\"" : 'null';
      $formattedQuery = sprintf($query, $after);

      $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'X-Shopify-Access-Token' => $this->token,
      ])->post($this->apiUrl, [
        'query' => $formattedQuery,
      ]);

      $json = $response->json();

      // Log::info($json);

      if (!isset($json['data']['orders']['edges'])) break;

      foreach ($json['data']['orders']['edges'] as $edge) {
        $order = $edge['node'];
        $orders[] = [
          'id' => $order['id'],
          'name' => $order['name'],
          'date' => $order['createdAt'],
          'total' => $order['totalPriceSet']['shopMoney']['amount'],
          'currency' => $order['totalPriceSet']['shopMoney']['currencyCode'],
          'items' => collect($order['lineItems']['edges'])->map(function ($itemEdge) {
            $item = $itemEdge['node'];
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

      $pageInfo = $json['data']['orders']['pageInfo'] ?? null;
      $hasNextPage = $pageInfo['hasNextPage'] ?? false;
      $endCursor = $pageInfo['endCursor'] ?? null;

      // Esperar medio segundo entre llamadas
      usleep(500000);
    }

    return $orders;
  }

  public function getTopProductsBetween($startDate, $endDate)
  {
    $orders = $this->getOrdersBetween($startDate, $endDate);

    $products = collect();

    foreach ($orders as $order) {

      foreach ($order['items'] as $item) {
        $key = $item['title'] . ' - ' . ($item['variant'] ?? 'N/A');

        // Obtener producto actual si existe
        $existing = $products->get($key, [
          'title' => $item['title'],
          'variant' => $item['variant'] ?? 'N/A',
          'image' => $item['image'] ?? null,
          'quantity' => 0,
          'total_sales' => 0.0,
        ]);

        // Acumular cantidades y ventas
        $existing['quantity'] += (int) $item['quantity'];
        $existing['total_sales'] += ((float) $item['price']) * (int) $item['quantity'];

        // Reemplazar el valor actualizado en la colección
        $products->put($key, $existing);
      }
    }


    return $products
      ->sortByDesc('quantity')
      ->values()
      ->toArray();
  }


  public function getDailyOrdersReport(int $days = 7): array
  {
    // Normalizar valor
    $days = max(1, $days);

    // Definir rango: últimos N días incluyendo hoy
    $endDate = Carbon::now('America/Lima')->endOfDay();
    $startDate = $endDate->copy()->subDays($days - 1)->startOfDay();

    // Traer órdenes del servicio interno (usa Y-m-d strings)
    $ordersRaw = $this->getOrdersBetween($startDate->toDateString(), $endDate->toDateString());
    $orders = collect($ordersRaw ?? []);

    // Si no vinieron órdenes, registrar para debug
    if ($orders->isEmpty()) {
      Log::warning('getDailyOrdersReport: getOrdersBetween returned empty', [
        'start' => $startDate->toDateString(),
        'end' => $endDate->toDateString(),
      ]);
    }

    // Helper que intenta extraer la fecha de creación desde varias claves posibles
    $extractCreatedAt = function ($order) {
      if (is_array($order)) {
        return $order['date'] ?? $order['createdAt'] ?? $order['created_at']
          ?? ($order['order']['createdAt'] ?? null)
          ?? ($order['node']['createdAt'] ?? null)
          ?? ($order['order']['created_at'] ?? null);
      }

      // si es objeto
      if (is_object($order)) {
        return $order->date ?? $order->createdAt ?? $order->created_at
          ?? ($order->order->createdAt ?? null)
          ?? ($order->node->createdAt ?? null)
          ?? ($order->order->created_at ?? null);
      }

      return null;
    };

    // Mapear cada orden a su fecha local (America/Lima)
    $mapped = $orders->map(function ($order) use ($extractCreatedAt) {
      $createdAt = $extractCreatedAt($order);
      if (!$createdAt) {
        return null; // lo filtraremos luego
      }

      try {
        $localDate = Carbon::parse($createdAt)->setTimezone('America/Lima')->toDateString();
      } catch (\Exception $e) {
        // si no se puede parsear
        Log::warning('getDailyOrdersReport: failed to parse createdAt', ['createdAt' => $createdAt]);
        return null;
      }

      return ['date' => $localDate, 'order' => $order];
    })->filter(); // eliminamos nulls

    // Agrupar por fecha local
    $grouped = $mapped->groupBy('date');

    // Construir el periodo con días consecutivos (inclusivo)
    $period = [];
    for ($i = 0; $i < $days; $i++) {
      $date = $startDate->copy()->addDays($i)->setTimezone('America/Lima')->toDateString();
      $count = $grouped->has($date) ? $grouped->get($date)->count() : 0;
      $period[] = [
        'date' => $date,
        'order_count' => $count,
      ];
    }

    return $period;
  }


}
