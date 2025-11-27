<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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

    public function getOrders(
        $limit,
        $cursor = null,
        $startDate = null,
        $endDate = null,
        $includes = ['customer', 'items', 'shippingAddress', 'shippingLines']
    ) {
        return $this->fetchOrders(
            $this->ordersQuery($limit, $cursor, $startDate, $endDate, $includes)
        );
    }

    private function fetchOrders(string $query)
    {
        $response = $this->graphql($query);

        if ($response->failed()) {
            Log::error('Error al obtener 贸rdenes', ['response' => $response]);
            return ['error' => 'No se pudieron obtener las 贸rdenes'];
        }

        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress']
        );

        // Aplicar el mapeo solo una vez aqu铆
        // $orders = collect($result['items'])
        //     ->map(fn($order) => $this->mapOrder($order))
        //     ->toArray();

        return [
            'orders'     => json_decode(json_encode($result['items'])),
            'pageInfo'   => $result['pageInfo'] ?? null,
            'lastCursor' => $result['lastCursor'] ?? null,
        ];
    }

    public function ordersQuery(

        $limit = 10,
        $cursor = null,
        $startDate = null,
        $endDate = null,
        $includes
    ) {

        // Fechas por defecto → último mes
        $startDate = $startDate ?? now()->subYear(10)->startOfDay()->toDateString(); // si quieres todos los años
        $endDate = $endDate ?? now()->endOfDay()->toDateString();

        // Cursor opcional (paginación)
        // Validar tipo de cursor
        if (is_array($cursor)) {
            $cursor = $cursor['endCursor'] ?? null;
        }

        // Cursor opcional (paginación)
        $afterClause = is_string($cursor) && !empty($cursor)
            ? ', after: "' . $cursor . '"'
            : '';

        // Filtro de búsqueda Shopify con AND explícitos
        $queryFilter = 'cancelled_at:null AND created_at:>=' . $startDate . ' AND created_at:<=' . $endDate; //indica la fecha, 10 anos por defecto

        // Log::info($queryFilter);

        // Query GraphQL como string plano (sin heredoc)
        return '
            {
                orders(
                    first: ' . $limit . ',
                    sortKey: CREATED_AT,
                    reverse: true' . $afterClause . ',
                    query: "' . $queryFilter . '"
                ) {
                    ' . $this->pageInfo . '
                    edges {
                        cursor
                        node {
                        ' . $this->orderQuery($includes) . '
                        }
                    }
                }
            }
        ';
    }

    protected function orderQuery(array $includes = []): string
    {
        $query = "
            id
            name
            createdAt
            updatedAt
            processedAt
            sourceName
            displayFinancialStatus
            displayFulfillmentStatus
            note
            fulfillmentOrders(first: 20) {
                edges {
                    node {
                        id
                        status
                        createdAt
                        requestStatus
                        updatedAt
                    }
                }
            }
            totalPriceSet {
                shopMoney {
                    amount
                    currencyCode
                }
            }
            " . (in_array('shippingLines', $includes) ? $this->shippingLinesQuery() : '') . "
            " . (in_array('shippingAddress', $includes) ? $this->shippingAddressQuery() : '') . "
            " . (in_array('customer', $includes) ? $this->customerQuery() : '') . "
            " . (in_array('items', $includes) ? $this->itemsQuery() : '') . "
            " . (in_array('events', $includes) ? $this->eventsQuery() : '') . "
        ";

        Log::info($query);

        return $query;
    }

    public function itemsQuery()
    {

        return  "
                    lineItems(first: 50) {
                        edges {
                            node {
                                name
                                quantity
                                originalUnitPriceSet {
                                    shopMoney {
                                        amount
                                        currencyCode
                                    }
                                }
                                variant {
                                    id
                                    title
                                    price
                                    product {
                                        title
                                        featuredImage { url }
                                    }
                                }
                            }
                        }
                    }
                ";
    }

    public function customerQuery()
    {
        return "
                customer {
                    id
                    firstName
                    lastName
                    email
                    phone
                    createdAt
                    tags
                    defaultAddress {
                        id
                        firstName
                        lastName
                        company
                        address1
                        address2
                        city
                        province
                        provinceCode
                        country
                        countryCodeV2
                        zip
                        phone
                        name
                        formatted
                    }

                }
        ";
    }

    public function eventsQuery()
    {

        return "
            events(first: 20, reverse: true) {
                edges {
                    node {
                    createdAt
                    message
                    }
                }
            }
        ";
    }

    public function shippingAddressQuery()
    {

        return "
            shippingAddress {
                firstName
                lastName
                name
                company
                address1
                address2
                city
                province
                provinceCode
                country
                countryCodeV2
                zip
                phone
                formatted
            }
        ";
    }


    public function shippingLinesQuery()
    {
        return "
            shippingLines(first: 1) {
                edges {
                    node {
                    title
                    originalPriceSet {
                        shopMoney {
                        amount
                        currencyCode
                        }
                    }
                    discountedPriceSet {
                        shopMoney {
                        amount
                        currencyCode
                        }
                    }
                    }
                }
            }
        ";
    }
}
