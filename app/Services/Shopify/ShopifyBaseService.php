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

            Log::info($query);

            // Ejecutar GraphQL
            $json = $this->graphql($query)->json();

            Log::info("imprimiendo el logo de graphql");
            Log::info($json);

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
            usleep(50000);
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

    protected function buildMonthPeriod(
        Carbon $startDate,
        int $months,
        Collection $grouped
    ): array {

        $period = [];

        for ($i = 0; $i < $months; $i++) {

            $monthStart = $startDate
                ->copy()
                ->addMonths($i)
                ->startOfMonth();

            $key  = $monthStart->format('Y-m');
            $date = $monthStart->toDateString();

            // Órdenes del mes
            $orders = $grouped[$key] ?? collect();

            // Total de ventas del mes
            $total = $orders->sum(function ($item) {
                return (float) (
                    $item['order']['totalPriceSet']['shopMoney']['amount']
                    ?? 0
                );
            });

            $period[] = [
                'date'        => $date,
                'order_count' => $orders->count(),
                'total'       => round($total, 2),
                'comision'       => round($total * 0.02, 2), // 2% de comisión
            ];
        }

        return $period;
    }

    public function isValidGid(string $id, ?string $resourceType = null): bool
    {
        if (!str_starts_with($id, 'gid://')) {
            return false;
        }

        $pattern = $resourceType
            ? "/^gid:\/\/shopify\/{$resourceType}\/\d+$/"
            : "/^gid:\/\/shopify\/[a-zA-Z]+\/\d+$/";

        return preg_match($pattern, $id) === 1;
    }

    /**
     * Extrae el ID numérico de un gid
     */
    public function extractNumericId(string $gid): string
    {
        $parts = explode('/', $gid);
        return end($parts);
    }

    /**
     * Convierte ID numérico a gid
     */
    public function toGid(string $numericId, string $resourceType = 'Order'): string
    {
        return "gid://shopify/{$resourceType}/{$numericId}";
    }
}
