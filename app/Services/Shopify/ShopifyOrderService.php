<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use Illuminate\Support\Facades\Log;

class ShopifyOrderService extends ShopifyBaseService
{

    public function getOrderByName(string $orderName)
    {

        $fields = $this->orderQuery(['shippingAddress', 'shippingLines']);

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


        $response = $this->graphql($query);

        // Log::info("hola");
        // Log::info($response);

        // if ($response->failed()) {
        //     Log::error('Error al obtener la orden ' . $orderName, ['response' => $response]);
        //     return ['error' => 'Error al obtener la orden '];
        // }

        // Log::info("Mundo");

        $data = GraphQLResponseHelper::normalizeSingle(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines']
        );

        //  $data = GraphQLResponseHelper::normalizeSingle($this->graphql($query));

        // Convertimos el array a objeto recursivamente
        return json_decode(json_encode($data));
    }

    public function getOrdersBetweenx($startDate, $endDate, $limit = 25, $cursor = null)
    {

        $orders = [];
        $hasNextPage = true;

        while ($hasNextPage) {

            $query = $this->ordersQuery($limit, $cursor, $startDate, $endDate, []);

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


    //orders
    public function getOrders(
        $limit,
        $cursor = null,
        $startDate = null,
        $endDate = null,
        $includes = ['customer','items', 'shippingAddress', 'shippingLines']
    ) {
        return $this->fetchOrders(
            $this->ordersQuery($limit, $cursor, $startDate, $endDate, $includes)
        );
    }

    public function getOrdersBetween($startDate, $endDate, $limit = 25, $cursor = null, $includes = [])
    {
        return $this->fetchOrders(
            $this->ordersQuery($limit, $cursor, $startDate, $endDate, $includes)
        );
    }


    public function getAllOrders()
    {
        $limit = 100; // 🔹 Shopify recomienda máximo 50
        $cursor = null;
        $hasNextPage = true;
        $allOrders = [];

        while ($hasNextPage) {
            //Usa getOrders() internamente
            $data = $this->getOrders($limit, $cursor, null, null, []); //array vacio para que no incluya nada

            if (isset($data['error'])) {
                Log::error('Error al obtener órdenes paginadas', ['cursor' => $cursor]);
                break;
            }

            //Acumula los resultados
            $orders = $data['orders'] ?? [];
            $allOrders = array_merge($allOrders, $orders);

            //Actualiza la paginación
            $pageInfo = $data['pageInfo'] ?? [];
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $cursor = $data['lastCursor'] ?? null;

            //Pausa de 0.5 segundos
            usleep(500000);
        }

        // 🔹 Devuelve todas las órdenes (puedes devolver también pageInfo si lo prefieres)
        return $allOrders;
    }


    public function getAllOrdersBetween($startDate = null, $endDate = null)
    {

        $limit = 50; // 🔹 Máximo por página recomendado por Shopify
        $cursor = null;
        $hasNextPage = true;
        $allOrders = [];

        while ($hasNextPage) {

            $data = $this->getOrdersBetween($startDate, $endDate, $limit, $cursor);

            if (isset($data['error'])) {
                Log::error('Error al obtener órdenes paginadas', ['cursor' => $cursor]);
                break;
            }

            $orders = $data['orders'] ?? [];
            $allOrders = array_merge($allOrders, $orders);

            // Actualizar paginación
            $pageInfo = $data['pageInfo'] ?? [];
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $cursor = $data['lastCursor'] ?? null;

            usleep(500000); // 0.5 segundos

        }

        // Log::info("Total de órdenes obtenidas: " . count($allOrders));

        return $allOrders;
    }

    /**
     * 🔹 Método reutilizable que ejecuta la consulta, maneja errores y normaliza la respuesta.
     */
    private function fetchOrders(string $query)
    {
        $response = $this->graphql($query);

        if ($response->failed()) {
            Log::error('Error al obtener órdenes', ['response' => $response]);
            return ['error' => 'No se pudieron obtener las órdenes'];
        }

        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress']
        );

        // Aplicar el mapeo solo una vez aquí
        // $orders = collect($result['items'])
        //     ->map(fn($order) => $this->mapOrder($order))
        //     ->toArray();

        return [
            'orders'     => json_decode(json_encode($result['items'])),
            'pageInfo'   => $result['pageInfo'] ?? null,
            'lastCursor' => $result['lastCursor'] ?? null,
        ];
    }
}
