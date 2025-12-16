<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopifyReportService extends ShopifyBaseService
{

  // ==================================== REPORTES BARRAS VERTICAL  ====================================

  public function getReportBarOrders(int $days = 7): array
  {
    $days = max(1, $days);

    // Crear rango de fechas
    [$startDate, $endDate] = $this->buildDateRange($days);

    // -------------------------------------------------------------
    // 1️⃣ — EL QUERY ESTÁ AQUÍ MISMO (plantilla)
    // -------------------------------------------------------------
    $queryTemplate = <<<GRAPHQL
    {
      orders(
        first: 100,
        sortKey: CREATED_AT,
        query: "financial_status:paid cancelled_at:null created_at:>=:start created_at:<=:end",
        after: :cursor
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
              shopMoney { amount currencyCode }
            }
          }
        }
      }
    }
    GRAPHQL;

    // -------------------------------------------------------------
    // 2️⃣ — QueryBuilder para reemplazar placeholders
    // -------------------------------------------------------------
    $queryBuilder = function (?string $cursor) use ($queryTemplate, $startDate, $endDate) {

      return str_replace(
        [':start', ':end', ':cursor'], //Elementos a reeemplazar
        [$startDate->toDateString(), $endDate->toDateString(), $cursor ? "\"$cursor\"" : 'null'], //Con estos valores
        $queryTemplate //En el template
      );
    };

    // -------------------------------------------------------------
    // 3️⃣ — Ejecutar el query builder ($queryBuilder) para traer los datos de Shopify
    // -------------------------------------------------------------
    $result = $this->getDataFromShopify(
      'orders',
      $queryBuilder,
      [] //podria ser 'lineItems'
    );

    // -------------------------------------------------------------
    // 4️⃣ — Normalizar items
    // -------------------------------------------------------------
    $orders = collect($result['items'] ?? []);

    if ($orders->isEmpty()) {
      Log::warning('getReportBarOrders: vacío', [
        'start' => $startDate->toDateString(),
        'end'   => $endDate->toDateString(),
      ]);
    }

    // Convertir fechas a local
    $mapped = $this->mapOrdersToLocalDate($orders);

    // Agrupar por fecha
    $grouped = $mapped->groupBy('date');

    // Construir el periodo completo
    return $this->buildPeriod($startDate, $days, $grouped);
  }

  public function getReportBarMonths(int $months = 12): array
  {
    $months = max(1, $months);

    // 🔑 Cache key estable por rango mensual
    $cacheKey = "report_bar_months:months_{$months}";

    return Cache::remember(
      $cacheKey,
      now()->addHours(24), // ⏱️ cache 24 horas
      function () use ($months) {

        // -------------------------------------------------------------
        // 1️⃣ — Rango de fechas por meses
        // -------------------------------------------------------------
        $endDate   = now()->endOfMonth();
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        // -------------------------------------------------------------
        // 2️⃣ — Query GraphQL
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
            {
              orders(
                first: 100,
                sortKey: CREATED_AT,
                query: "financial_status:paid cancelled_at:null created_at:>=:start created_at:<=:end",
                after: :cursor
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
                      shopMoney { amount currencyCode }
                    }
                  }
                }
              }
            }
            GRAPHQL;

        // -------------------------------------------------------------
        // 3️⃣ — QueryBuilder
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use ($queryTemplate, $startDate, $endDate) {

          return str_replace(
            [':start', ':end', ':cursor'],
            [
              $startDate->toDateString(),
              $endDate->toDateString(),
              $cursor ? "\"$cursor\"" : 'null'
            ],
            $queryTemplate
          );
        };

        // -------------------------------------------------------------
        // 4️⃣ — Obtener datos desde Shopify
        // -------------------------------------------------------------
        $result = $this->getDataFromShopify(
          'orders',
          $queryBuilder,
          []
        );

        // -------------------------------------------------------------
        // 5️⃣ — Normalizar
        // -------------------------------------------------------------
        $orders = collect($result['items'] ?? []);

        if ($orders->isEmpty()) {
          Log::warning('getReportBarMonths: vacío', [
            'start' => $startDate->toDateString(),
            'end'   => $endDate->toDateString(),
          ]);
        }

        // Convertir a fecha local
        $mapped = $this->mapOrdersToLocalDate($orders);

        // -------------------------------------------------------------
        // 6️⃣ — Agrupar por MES (Y-m)
        // -------------------------------------------------------------
        $grouped = $mapped->groupBy(function ($item) {
          return Carbon::parse($item['date'])->format('Y-m');
        });

        // -------------------------------------------------------------
        // 7️⃣ — Construir periodo mensual
        // -------------------------------------------------------------
        return $this->buildMonthPeriod($startDate, $months, $grouped);
      }
    );
  }

  // ==================================== REPORTES DEL PRODUCTO MAS VENDIDOS ORDENADOS DE MAYOR A MENOR  ====================================

  public function getReportTopSellingProducts($days = 3650) // 10 años
  {

    // Crear rango de fechas
    [$startDate, $endDate] = $this->buildDateRange($days); //3650 quiere decir 10 años

    $queryTemplate = <<<GRAPHQL
                  {
                    orders(
                      first: 100,
                      sortKey: CREATED_AT,
                      query: "financial_status:paid cancelled_at:null created_at:>=:start created_at:<=:end",
                      after: :cursor
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

    // -------------------------------------------------------------
    // 2️⃣ — QueryBuilder para reemplazar placeholders
    // -------------------------------------------------------------

    $queryBuilder = function (?string $cursor) use ($queryTemplate, $startDate, $endDate) {

      return str_replace(
        [':start', ':end', ':cursor'], //Elementos a reeemplazar
        [$startDate->toDateString(), $endDate->toDateString(), $cursor ? "\"$cursor\"" : 'null'], //Con estos valores
        $queryTemplate //En el template
      );
    };

    //fin del QueryBuilder

    // -------------------------------------------------------------
    // 3️⃣ — Ejecutar el query builder ($queryBuilder) para traer los datos de Shopify
    // -------------------------------------------------------------
    // getReportTopSellingProducts
    $result = $this->getDataFromShopify( //Ejecuta el codigo del queryBuilder y trae los datos de shopify con un while para no cargar a shopify
      'orders',
      $queryBuilder,
      ['lineItems'] //podria ser 'lineItems'
    );

    // -------------------------------------------------------------
    // 4️⃣ — Normalizar items
    // -------------------------------------------------------------
    $orders = collect($result['items'] ?? []);

    //collect() transforma un array en un objeto Collection. Esto permite usar métodos como: ,map(),filter(),groupBy(),pluck(),sortBy(),count(),where()
    //formato que trae shopify
    /*
       {
      "id":"gid:\/\/shopify\/Order\/6428652896480",
      "name":"#6981",
      "createdAt":"2025-11-22T16:43:00Z",
      "totalPriceSet":{
         "shopMoney":{
            "amount":"99.9",
            "currencyCode":"PEN"
         }
      },
      "lineItems":[
         {
            "name":"Sweater Taylor Avena 1 - Standar",
            "quantity":1,
            "variant":{
               "id":"gid:\/\/shopify\/ProductVariant\/47794765267168",
               "title":"Standar",
               "price":"84.90",
               "image":null,
               "product":{
                  "title":"Sweater Taylor Avena 1",
                  "createdAt":"2025-09-27T20:01:04Z",
                  "featuredImage":{
                     "url":"https:\/\/cdn.shopify.com\/s\/files\/1\/0667\/7204\/1952\/files\/9AB3B43E-9444-417F-A6CB-BA2BDB1A2521.jpg?v=1759013825"
                  }
               }
            }
         }
      ],
      "cursor":"eyJsYXN0X2lkIjo2NDI4NjUyODk2NDgwLCJsYXN0X3ZhbHVlIjoiMjAyNS0xMS0yMiAxNjo0MzowMC41ODU3MzYifQ=="
   },
    */
    $products = collect();

    // Log::info($orders);

    foreach ($orders as $order) {

      foreach ($order['lineItems'] as $item) {

        $title       = $item['variant']['product']['title'] ?? $item['name'];
        $variant     = $item['variant']['title'] ?? 'N/A';
        $price       = (float) ($item['variant']['price'] ?? 0);
        $qty         = (int) $item['quantity'];
        $image       = $item['variant']['product']['featuredImage']['url'] ?? null;

        // Clave única producto + variante
        $key = "{$title} - {$variant}";

        // Producto acumulado si existe
        $existing = $products->get($key, [
          'title'       => $title,
          'variant'     => $variant,
          'image'       => $image,
          'quantity'    => 0,
          'total_sales' => 0.0,
        ]);

        // Sumar cantidades y ventas
        $existing['quantity']    += $qty;
        $existing['total_sales'] += ($price * $qty);

        // Guardar actualizado
        $products->put($key, $existing);
      }
    }

    Log::info($products);

    return $products
      ->sortByDesc('quantity')   // También puedes ordenar por 'total_sales'
      ->values()
      ->toArray();
  }

  // ==================================== REPORTE DE VENTAS TOTALES POR AÑO Y MES ====================================

  //== NEW getReportSalesByYearMonth() ==//

  public function getReportSalesByYearMonth()
  {

    // CLAVE DE CACHE (usa la tienda)
    $cacheKey = "shopify_report_sales_year_month_";

    // TIEMPO DE CACHE (puedes cambiarlo)
    $cacheTTL = now()->addHours(24); // o addDays(7), etc.

    return Cache::remember($cacheKey, $cacheTTL, function () {


      // Crear rango de fechas
      [$startDate, $endDate] = $this->buildDateRange(3650); //10 años

      // -------------------------------------------------------------
      // 1 — EL QUERY ESTÁ AQUÍ MISMO (plantilla)
      // -------------------------------------------------------------
      $queryTemplate = <<<GRAPHQL
    {
      orders(
        first: 200,
        sortKey: CREATED_AT,
        query: "financial_status:paid cancelled_at:null created_at:>=:start created_at:<=:end",
        after: :cursor
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
            }
          }
        }
    }
    GRAPHQL;


      // -------------------------------------------------------------
      // 2 — QueryBuilder para reemplazar placeholders
      // -------------------------------------------------------------
      $queryBuilder = function (?string $cursor) use ($queryTemplate, $startDate, $endDate) {

        return str_replace(
          [':start', ':end', ':cursor'], //Elementos a reeemplazar
          [$startDate->toDateString(), $endDate->toDateString(), $cursor ? "\"$cursor\"" : 'null'], //Con estos valores
          $queryTemplate //En el template
        );
      };

      // -------------------------------------------------------------
      // 3 — Ejecutar el query builder ($queryBuilder) para traer los datos de Shopify
      // -------------------------------------------------------------
      $result = $this->getDataFromShopify(
        'orders',
        $queryBuilder,
        ['lineItems'] //podria ser 'lineItems'
      );

      // -------------------------------------------------------------
      // 4 — Normalizar items
      // -------------------------------------------------------------
      $orders = collect($result['items'] ?? []);

      if ($orders->isEmpty()) {
        Log::warning('getReportBarOrders: vacío', [
          'start' => $startDate->toDateString(),
          'end'   => $endDate->toDateString(),
        ]);
      }

      // Construir el reporte

      $report = [
        'total_sales' => 0,
        'years' => [],
      ];

      foreach ($orders as $order) {

        $date = Carbon::parse($order['createdAt']);
        $year = $date->format('Y');
        $month = $date->format('m');
        $amount = floatval($order['totalPriceSet']['shopMoney']['amount'] ?? 0);

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
    });
  }
}
