<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ShopifyReportService extends ShopifyBaseService
{
    protected string $pageInfo;

    public function getDailyOrdersReport_1(int $days = 7): array
    {
        // Normalizar valor
        $days = max(1, $days);

        // Definir rango: últimos N días incluyendo hoy
        $endDate = Carbon::now('America/Lima')->endOfDay();
        $startDate = $endDate->copy()->subDays($days - 1)->startOfDay();

        Log::info($startDate);

        // Traer órdenes del servicio interno (usa Y-m-d strings)
        $ordersRaw = $this->getOrdersDailyBetween($startDate->toDateString(), $endDate->toDateString());

        $orders = collect($ordersRaw['items'] ?? []);

        // Si no vinieron órdenes, registrar para debug
        if ($orders->isEmpty()) {
            Log::warning('getDailyOrdersReport: getOrdersBetween returned empty', [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ]);
        }

        // Mapear cada orden a su fecha local (America/Lima)
        $mapped = $orders->map(function ($order) {
            $createdAt = $order['createdAt'];
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

    protected function getOrdersDailyBetween_($startDate, $endDate)
    {
        $hasNextPage = true;

        while ($hasNextPage) {

            $endCursor = null;
            $after = $endCursor ? "\"{$endCursor}\"" : "null";

            $query = <<<GRAPHQL
            {
              orders(
                first: 50,
                sortKey: CREATED_AT,
                query: "financial_status:paid cancelled_at:null created_at:>={$startDate} created_at:<={$endDate}",
                after: $after
              ) {
                pageInfo {
                  hasNextPage
                  endCursor
                }
                edges {
                  cursor
                  node {
                    id
                    name
                    createdAt
                    totalPriceSet { 
                        shopMoney {
                            amount currencyCode 
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

            $json = $this->graphql($query)->json();

            Log::info($json);

            // Log::info($json);

            if (!isset($json['data']['orders']['edges'])) {
                break;
            }

            //Guardar edges crudos
            foreach ($json['data']['orders']['edges'] as $edge) {
                $allEdges[] = $edge;
            }

            $pageInfo = $json['data']['orders']['pageInfo'] ?? null;
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $endCursor = $pageInfo['endCursor'] ?? null;

            // Esperar medio segundo entre llamadas
            usleep(500000);
        }

        $finalResponse = [
            'data' => [
                'orders' => [
                    'edges' => $allEdges
                ]
            ]
        ];

        // Log::info($allEdges);

        $result = GraphQLResponseHelper::normalizeEntity(
            $finalResponse,
            'orders', //puede ser products pero le pondremos data para estandarizar
            ['lineItems']
        );

        Log::info("imprimiendo result");

        Log::info($result);

        return $result;
    }

    protected function getOrdersDailyBetweenxx($startDate, $endDate)
    {
        $hasNextPage = true;

        while ($hasNextPage) {

            $endCursor = null;
            $after = $endCursor ? "\"{$endCursor}\"" : "null";

            $query = <<<GRAPHQL
            {
              orders(
                first: 50,
                sortKey: CREATED_AT,
                query: "financial_status:paid cancelled_at:null created_at:>={$startDate} created_at:<={$endDate}",
                after: $after
              ) {
                pageInfo {
                  hasNextPage
                  endCursor
                }
                edges {
                  cursor
                  node {
                    id
                    name
                    createdAt
                    totalPriceSet { 
                        shopMoney {
                            amount currencyCode 
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

            $json = $this->graphql($query)->json();

            Log::info($json);

            // Log::info($json);

            if (!isset($json['data']['orders']['edges'])) {
                break;
            }

            //Guardar edges crudos
            foreach ($json['data']['orders']['edges'] as $edge) {
                $allEdges[] = $edge;
            }

            $pageInfo = $json['data']['orders']['pageInfo'] ?? null;
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $endCursor = $pageInfo['endCursor'] ?? null;

            // Esperar medio segundo entre llamadas
            usleep(500000);
        }

        $finalResponse = [
            'data' => [
                'orders' => [
                    'edges' => $allEdges
                ]
            ]
        ];

        // Log::info($allEdges);

        $result = GraphQLResponseHelper::normalizeEntity(
            $finalResponse,
            'orders', //puede ser products pero le pondremos data para estandarizar
            ['lineItems']
        );

        Log::info("imprimiendo result");

        Log::info($result);

        return $result;
    }

    //===========================================================//

    public function getDailyOrdersReport(int $days = 7): array
    {
        $days = max(1, $days);

        // Crear rango de fechas
        [$startDate, $endDate] = $this->buildDateRange($days);

        // Llamada a Shopify (normalizada)
        $result = $this->getOrdersDailyBetween(
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        $orders = collect($result['items'] ?? []);

        if ($orders->isEmpty()) {
            Log::warning('getDailyOrdersReport: responde vacio', [
                'start' => $startDate->toDateString(),
                'end'   => $endDate->toDateString(),
            ]);
        }

        // Convertir fechas a local
        $mapped = $this->mapOrdersToLocalDate($orders);

        // Agrupar por fecha
        $grouped = $mapped->groupBy('date');

        // Construir array final
        return $this->buildPeriod($startDate, $days, $grouped);
    }

    /**
     * Obtiene TODAS las órdenes entre fechas (paginado completo)
     */
    public function getOrdersDailyBetween(string $start, string $end): array
    {
        return $this->fetchAllEdges(

            'orders',

            // QueryBuilder (callable dinámico)
            function (?string $cursor) use ($start, $end) {

                $cursorValue = $cursor ? "\"$cursor\"" : 'null';

                return <<<GRAPHQL
                {
                  orders(
                    first: 50,
                    sortKey: CREATED_AT,
                    query: "financial_status:paid cancelled_at:null created_at:>={$start} created_at:<={$end}",
                    after: $cursorValue
                  ) {
                    pageInfo {
                      hasNextPage
                      endCursor
                    }
                    edges {
                      cursor
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
                                  featuredImage { url }
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
            },

            // campos adicionales a normalizar
            ['lineItems']
        );
    }

    /**
     * Rango de fechas consecutivas
     */
    protected function buildDateRange(int $days): array
    {
        $end = Carbon::now('America/Lima')->endOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();
        return [$start, $end];
    }

    /**
     * Mapea cada orden con su fecha local
     */
    protected function mapOrdersToLocalDate(Collection $orders): Collection
    {
        return $orders->map(function ($order) {

            if (!isset($order['createdAt'])) return null;

            try {
                $date = Carbon::parse($order['createdAt'])
                    ->setTimezone('America/Lima')
                    ->toDateString();
            } catch (\Throwable $e) {
                Log::warning('Invalid createdAt', ['value' => $order['createdAt']]);
                return null;
            }

            return [
                'date'  => $date,
                'order' => $order,
            ];
        })->filter();
    }

    /**
     * Arma el período completo con conteo por día
     */
    protected function buildPeriod(
        Carbon $startDate,
        int $days,
        Collection $grouped
    ): array {
        $period = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();

            $period[] = [
                'date'        => $date,
                'order_count' => ($grouped[$date] ?? collect())->count(),
            ];
        }

        return $period;
    }
}
