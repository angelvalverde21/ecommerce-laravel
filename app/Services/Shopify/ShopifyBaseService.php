<?php

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
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
    protected function graphql(string $query)
    {
        Log::info($this->apiUrl);
        Log::info($this->token);

        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->token,
        ])->post($this->apiUrl, ['query' => $query]);
    }


    protected function tracking($order_id){

    }

    protected function mapOrder(array $order): array
    {
        return [
            'id'       => $order['id'],
            'name'     => $order['name'],
            'note'     => $order['note'],
            'financial_status'     => $order['displayFinancialStatus'],
            'status'     => $order['displayFulfillmentStatus'],
            'origen' => $order['sourceName'],
            'events' => $order['events'] ?? null,
            'created_at'     => $order['createdAt'],
            'total'    => $order['totalPriceSet']['shopMoney']['amount'],
            'currency' => $order['totalPriceSet']['shopMoney']['currencyCode'],
            // 🧾 Cliente (si existe)
            'customer' => isset($order['customer']) ? [
                'id'         => $order['customer']['id'] ?? null,
                'first_name' => $order['customer']['firstName'] ?? null,
                'last_name'  => $order['customer']['lastName'] ?? null,
                'email'      => $order['customer']['email'] ?? null,
                'phone'      => $order['customer']['phone'] ?? null,
                'tags'       => $order['customer']['tags'] ?? [],
                'created_at' => $order['customer']['createdAt'] ?? null,
                'address'    => [
                    'address1' => $order['customer']['defaultAddress']['address1'] ?? null,
                    'address2' => $order['customer']['defaultAddress']['address2'] ?? null,
                    'city'     => $order['customer']['defaultAddress']['city'] ?? null,
                    'province' => $order['customer']['defaultAddress']['province'] ?? null,
                ],
            ] : null,
            'items' => isset($order['lineItems'])
                ? collect($order['lineItems'])->map(function ($item) {
                    return [
                        'title'    => $item['variant']['product']['title'] ?? $item['name'],
                        'variant'  => $item['variant']['title'] ?? null,
                        'price'    => $item['variant']['price'] ?? null,
                        'quantity' => $item['quantity'],
                        'image'    => $item['variant']['product']['featuredImage']['url'] ?? null,
                    ];
                })->toArray()
                : null,
        ];
    }

    //Empiezan los queries

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
}
