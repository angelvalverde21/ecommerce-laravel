<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

abstract class ShopifyBaseService
{
    protected string $apiUrl = "";
    protected string $token = "";
    protected string $pageInfo = "";

    public function __construct()
    {
        $this->apiUrl = sprintf(
            "https://%s.myshopify.com/admin/api/%s/graphql.json",
            config('shopify.store'),
            config('shopify.version')
        );

        $this->token = config('shopify.token');

        $this->pageInfo = <<<GQL
            pageInfo {
              hasNextPage
              endCursor
            }
        GQL;
    }

    /**
     * 🔁 Ejecuta una query GraphQL en una sola línea
     */
    protected function graphql(string $query, ?array $variables = null)
    {
        $payload = [
            'query' => $query,
        ];

        // Solo enviar variables si existen
        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->token,
        ])->post($this->apiUrl, $payload);
    }
    //==================================== NUEVOS METODOS  ====================================

    protected function getDataFromShopify(
        string $rootField, //el nombre del campo raiz ej: products, orders
        callable $buildQuery, // función que construye el query
        array $normalizeChildren = [] // campos hijos para normalizar
    ): array {

        $allEdges   = [];
        $hasNextPage = true;
        $endCursor   = null;

        $paginas = 0;

        while ($hasNextPage) {

            Log::info("--- Página: " . (++$paginas) . " ---");

            // Construir query por callback
            $query = $buildQuery($endCursor); //aqui se le pasa el cursor

            // Ejecutar GraphQL
            $json = $this->graphql($query)->json();

            // Log::info("imprimiendo el logo de graphql");
            // Log::info($json);

            // Si no hay edges → terminar
            if (empty($json['data'][$rootField]['edges'])) {
                break;
            }

            // Acumular edges
            foreach ($json['data'][$rootField]['edges'] as $edge) {
                $allEdges[] = $edge;
            }

            // Paginación
            $pageInfo = $json['data'][$rootField]['pageInfo'] ?? null;
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $endCursor = $pageInfo['endCursor'] ?? null; //el endCursor para la siguiente página, y se le inyecta al query builder en :cursor

            Log::info($pageInfo);

            // Esperar medio segundo por límites Shopify
            usleep(500000);
        }

        // Normalizar estructura final
        return GraphQLResponseHelper::normalizeEntity([
            'data' => [
                $rootField => ['edges' => $allEdges]
            ]
        ], $rootField, $normalizeChildren);
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
