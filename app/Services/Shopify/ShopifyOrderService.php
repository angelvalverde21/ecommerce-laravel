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

        $fields = $this->orderQuery(['shippingAddress', 'shippingLines', 'items']);

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

    public function getOrderById(string $orderId)
    {
        // Validar que el ID tenga el formato correcto
        if (!str_starts_with($orderId, 'gid://shopify/Order/')) {
            throw new \InvalidArgumentException(
                "ID de orden inválido. Debe ser formato: gid://shopify/Order/xxxxx"
            );
        }

        $fields = $this->orderQuery(['shippingAddress', 'shippingLines', 'items']);

        $query = <<<GQL
        {
          node(id: "{$orderId}") {
            ... on Order {
              {$fields}
            }
          }
        }
        GQL;

        $response = $this->graphql($query);

        // Normalizar la respuesta (usando 'node' en lugar de 'orders')
        $data = GraphQLResponseHelper::normalizeSingle(
            $response,
            'node',  // Cambio clave: 'node' en lugar de 'orders'
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines']
        );

        // Verificar si se encontró la orden
        if (empty($data)) {
            throw new \Exception("No se encontró la orden con ID: {$orderId}");
        }

        return json_decode(json_encode($data));
    }


    public function orderByNumber(string $orderNumber, $includes = ['customer', 'items', 'shippingAddress', 'shippingLines'])
    {
        // Shopify espera el #
        if (!str_starts_with($orderNumber, '#')) {
            $orderNumber = '#' . $orderNumber;
        }

        $queryFilter = 'name:' . $orderNumber;

        return $this->fetchOrder(
            '
                {
                    orders(
                        first: 1,
                        query: "' . $queryFilter . '"
                    ) {
                        edges {
                            node {
                                ' . $this->orderQuery($includes) . '
                            }
                        }
                    }
                }
            '
        );
    }

    private function fetchOrders(string $query)
    {
        $response = $this->graphql($query);

        // Log::info($response);

        if ($response->failed()) {
            Log::error('Error al obtener 贸rdenes', ['response' => $response]);
            return ['error' => 'No se pudieron obtener las 贸rdenes'];
        }

        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress', 'fulfillmentOrders']
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

    private function fetchOrder(string $query)
    {
        $response = $this->graphql($query);

        // Log::info($response);

        if ($response->failed()) {
            Log::error('Error al obtener 贸rdenes', ['response' => $response]);
            return ['error' => 'No se pudieron obtener las 贸rdenes'];
        }

        $result = GraphQLResponseHelper::normalizeSingle(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress', 'fulfillmentOrders']
        );

        // Log::info("viendo que hay en result");

        // Log::info($result);

        // Aplicar el mapeo solo una vez aqu铆
        // $orders = collect($result['items'])
        //     ->map(fn($order) => $this->mapOrder($order))
        //     ->toArray();

        return $result;
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
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
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
            tags
            note
            fulfillmentOrders(first: 5) {
                edges {
                    node {
                        id
                        status
                        createdAt
                        requestStatus
                        updatedAt
                        assignedLocation {
                            name
                        }
                        lineItems(first: 10) {
                            edges {
                                node {
                                    id
                                    totalQuantity
                                    sku
                                    lineItem {
                                        id
                                        title
                                    }
                                }
                            }
                        }
                        fulfillments(first: 5) {
                            edges {
                                node {
                                    id
                                    status
                                    createdAt
                                    updatedAt
                                    trackingInfo {
                                        number
                                        url
                                        company
                                    }
                                    fulfillmentLineItems(first: 10) {
                                        edges {
                                            node {
                                                id
                                                quantity
                                            }
                                        }
                                    }
                                }
                            }
                        }
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

        // Log::info($query);

        return $query;
    }

    public function itemsQuery()
    {

        return  "
                    lineItems(first: 10) {
                        edges {
                            node {
                                id
                                name
                                quantity
                                originalUnitPriceSet {
                                    shopMoney {
                                        amount
                                        currencyCode
                                    }
                                }
                                discountedUnitPriceSet {
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
                    numberOfOrders
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
            events(first: 10, reverse: true) {
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
            totalShippingPriceSet {
                shopMoney {
                amount
                currencyCode
                }
            }
        ";
    }

    public function getOrdersPendingx(int $days = 30): array
    {

        Log::info('getOrdersPending');
        $days = max(1, $days);

        // Crear rango de fechas
        [$startDate, $endDate] = $this->buildDateRange($days);

        // -------------------------------------------------------------
        // 1️⃣ — EL QUERY ESTÁ AQUÍ MISMO (plantilla)
        // -------------------------------------------------------------

        $shippingLinesQuery = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery = $this->customerQuery();
        $itemsQuery = $this->itemsQuery();
        $eventsQuery = $this->eventsQuery();

        $queryTemplate = <<<GRAPHQL
                                {
                                orders(
                                    first: 100,
                                    sortKey: CREATED_AT,
                                    reverse: true,
                                    query: "financial_status:paid cancelled_at:null fulfillment_status:unfulfilled created_at:>=:start created_at:<=:end",
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
                                            $shippingLinesQuery
                                            $shippingAddressQuery
                                            $customerQuery
                                            $itemsQuery
                                            $eventsQuery
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
            ['lineItems'] //podria ser 'lineItems'
        );

        // -------------------------------------------------------------
        // 4️⃣ — Normalizar items
        // -------------------------------------------------------------
        Log::info($result['items']);
        return $result;
        // $orders = collect($result['items'] ?? []);

    }

    public function getOrdersPending(int $limit = 20, $cursor = null): array
    {
        Log::info('getOrdersPending');

        // Asegurar límite mínimo
        $limit = max(1, $limit);

        // -------------------------------------------------------------
        // 1️⃣ — Sub-queries para evitar repetición
        // -------------------------------------------------------------
        $shippingLinesQuery   = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery        = $this->customerQuery();
        $itemsQuery           = $this->itemsQuery();
        $eventsQuery          = $this->eventsQuery();

        // -------------------------------------------------------------
        // 2️⃣ — GraphQL Template SIN FECHAS, usando LIMIT
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
                                {
                                    orders(
                                        first: :limit,
                                        sortKey: CREATED_AT,
                                        reverse: true,
                                        query: "financial_status:paid cancelled_at:null fulfillment_status:unfulfilled",
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
                                                $shippingLinesQuery
                                                $shippingAddressQuery
                                                $customerQuery
                                                $itemsQuery
                                                $eventsQuery
                                            }
                                        }
                                    }
                                }
                            GRAPHQL;

        // -------------------------------------------------------------
        // Reemplazo dinámico únicamente para cursor y limit
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use ($queryTemplate, $limit) {
            return str_replace(
                [':limit', ':cursor'],
                [
                    $limit,
                    $cursor ? "\"$cursor\"" : 'null'
                ],
                $queryTemplate
            );
        };

        // -------------------------------------------------------------
        // 4️⃣ — Ejecutar contra Shopify
        // -------------------------------------------------------------

        $response = $this->graphql($queryBuilder($cursor));


        // Log::info($result['items'] ?? []);
        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            []
        );

        return [
            'orders'     => json_decode(json_encode($result['items'])),
            'pageInfo'   => $result['pageInfo'] ?? null,
            'lastCursor' => $result['lastCursor'] ?? null,
        ];

        // return $result;
    }


    public function getOrdersPreparedx(int $days = 30): array
    {

        Log::info('getOrdersPending');
        $days = max(1, $days);

        // Crear rango de fechas
        [$startDate, $endDate] = $this->buildDateRange($days);

        // -------------------------------------------------------------
        // 1️⃣ — EL QUERY ESTÁ AQUÍ MISMO (plantilla)
        // -------------------------------------------------------------

        $shippingLinesQuery = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery = $this->customerQuery();
        $itemsQuery = $this->itemsQuery();
        $eventsQuery = $this->eventsQuery();

        $queryTemplate = <<<GRAPHQL
                                {
                                orders(
                                    first: 100,
                                    sortKey: CREATED_AT,
                                    reverse: true,
                                    query: "financial_status:paid cancelled_at:null fulfillment_status:fulfilled created_at:>=:start created_at:<=:end",
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
                                            $shippingLinesQuery
                                            $shippingAddressQuery
                                            $customerQuery
                                            $itemsQuery
                                            $eventsQuery
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
            ['lineItems'] //podria ser 'lineItems'
        );

        // -------------------------------------------------------------
        // 4️⃣ — Normalizar items
        // -------------------------------------------------------------
        Log::info($result['items']);
        return $result;
        // $orders = collect($result['items'] ?? []);

    }

    public function getOrdersPrepared(int $limit = 20, $cursor = null): array
    {
        Log::info('getOrdersPrepared');

        // Asegurar límite mínimo
        $limit = max(1, $limit);

        // -------------------------------------------------------------
        // 1️⃣ — Sub-queries para evitar repetición
        // -------------------------------------------------------------
        $shippingLinesQuery   = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery        = $this->customerQuery();
        $itemsQuery           = $this->itemsQuery();
        $eventsQuery          = $this->eventsQuery();

        // -------------------------------------------------------------
        // 2️⃣ — GraphQL Template SIN FECHAS, usando LIMIT
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
                                {
                                    orders(
                                        first: :limit,
                                        sortKey: CREATED_AT,
                                        reverse: true,
                                        query: "financial_status:paid  tag:aylin cancelled_at:null fulfillment_status:fulfilled",
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
                                                $shippingLinesQuery
                                                $shippingAddressQuery
                                                $customerQuery
                                                $itemsQuery
                                                $eventsQuery
                                            }
                                        }
                                    }
                                }
                            GRAPHQL;

        // -------------------------------------------------------------
        // Reemplazo dinámico únicamente para cursor y limit
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use ($queryTemplate, $limit) {
            return str_replace(
                [':limit', ':cursor'],
                [
                    $limit,
                    $cursor ? "\"$cursor\"" : 'null'
                ],
                $queryTemplate
            );
        };

        // -------------------------------------------------------------
        // 4️⃣ — Ejecutar contra Shopify
        // -------------------------------------------------------------

        $response = $this->graphql($queryBuilder($cursor));


        // Log::info($result['items'] ?? []);
        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress', 'fulfillmentOrders']
        );

        return [
            'orders'     => json_decode(json_encode($result['items'])),
            'pageInfo'   => $result['pageInfo'] ?? null,
            'lastCursor' => $result['lastCursor'] ?? null,
        ];

        // return $result;
    }

    public function getOrdersAylin(int $limit = 20, $cursor = null): array
    {
        Log::info('getOrdersPrepared');

        // Asegurar límite mínimo
        $limit = max(1, $limit);

        // -------------------------------------------------------------
        // 1️⃣ — Sub-queries para evitar repetición
        // -------------------------------------------------------------
        $shippingLinesQuery   = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery        = $this->customerQuery();
        $itemsQuery           = $this->itemsQuery();
        $eventsQuery          = $this->eventsQuery();

        // -------------------------------------------------------------
        // 2️⃣ — GraphQL Template SIN FECHAS, usando LIMIT
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
                                {
                                    orders(
                                        first: :limit,
                                        sortKey: CREATED_AT,
                                        reverse: true,
                                        query: "financial_status:paid  tag:aylin cancelled_at:null fulfillment_status:fulfilled created_at:>=2026-01-07 created_at:<=2026-02-06",
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
                                                $itemsQuery
                                            }
                                        }
                                    }
                                }
                            GRAPHQL;

        // -------------------------------------------------------------
        // Reemplazo dinámico únicamente para cursor y limit
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use ($queryTemplate, $limit) {
            return str_replace(
                [':limit', ':cursor'],
                [
                    $limit,
                    $cursor ? "\"$cursor\"" : 'null'
                ],
                $queryTemplate
            );
        };

        // -------------------------------------------------------------
        // 4️⃣ — Ejecutar contra Shopify
        // -------------------------------------------------------------

        $response = $this->graphql($queryBuilder($cursor));


        // Log::info($result['items'] ?? []);
        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress']
        );

        return [
            'orders'     => json_decode(json_encode($result['items'])),
            'pageInfo'   => $result['pageInfo'] ?? null,
            'lastCursor' => $result['lastCursor'] ?? null,
        ];

        // return $result;
    }

    public function getOrdersYen(int $limit = 20, $cursor = null): array
    {
        Log::info('getOrdersPrepared');

        // Asegurar límite mínimo
        $limit = max(1, $limit);

        // -------------------------------------------------------------
        // 1️⃣ — Sub-queries para evitar repetición
        // -------------------------------------------------------------
        $shippingLinesQuery   = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery        = $this->customerQuery();
        $itemsQuery           = $this->itemsQuery();
        $eventsQuery          = $this->eventsQuery();

        // -------------------------------------------------------------
        // 2️⃣ — GraphQL Template SIN FECHAS, usando LIMIT
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
                                {
                                    orders(
                                        first: :limit,
                                        sortKey: CREATED_AT,
                                        reverse: true,
                                        query: "financial_status:paid  tag:JENNIFER cancelled_at:null fulfillment_status:fulfilled created_at:>=2026-01-07 created_at:<=2026-02-06",
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
                                                $itemsQuery
                                            }
                                        }
                                    }
                                }
                            GRAPHQL;

        // -------------------------------------------------------------
        // Reemplazo dinámico únicamente para cursor y limit
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use ($queryTemplate, $limit) {
            return str_replace(
                [':limit', ':cursor'],
                [
                    $limit,
                    $cursor ? "\"$cursor\"" : 'null'
                ],
                $queryTemplate
            );
        };

        // -------------------------------------------------------------
        // 4️⃣ — Ejecutar contra Shopify
        // -------------------------------------------------------------

        $response = $this->graphql($queryBuilder($cursor));


        // Log::info($result['items'] ?? []);
        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress']
        );

        return [
            'orders'     => json_decode(json_encode($result['items'])),
            'pageInfo'   => $result['pageInfo'] ?? null,
            'lastCursor' => $result['lastCursor'] ?? null,
        ];

        // return $result;
    }

    public function getOrdersByTag($tag, int $limit = 20, $cursor = null): array
    {
        Log::info('getOrdersPrepared');

        // Asegurar límite mínimo
        $limit = max(1, $limit);

        // -------------------------------------------------------------
        // 1️⃣ — Sub-queries para evitar repetición
        // -------------------------------------------------------------
        $shippingLinesQuery   = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery        = $this->customerQuery();
        $itemsQuery           = $this->itemsQuery();
        $eventsQuery          = $this->eventsQuery();

        // -------------------------------------------------------------
        // 2️⃣ — GraphQL Template SIN FECHAS, usando LIMIT
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
                                {
                                    orders(
                                        first: :limit,
                                        sortKey: CREATED_AT,
                                        reverse: true,
                                        query: "financial_status:paid  tag:$tag cancelled_at:null fulfillment_status:fulfilled created_at:>=2026-01-07 created_at:<=2026-02-06",
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
                                                tags
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
                                                $itemsQuery
                                            }
                                        }
                                    }
                                }
                            GRAPHQL;

        // -------------------------------------------------------------
        // Reemplazo dinámico únicamente para cursor y limit
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use ($queryTemplate, $limit) {
            return str_replace(
                [':limit', ':cursor'],
                [
                    $limit,
                    $cursor ? "\"$cursor\"" : 'null'
                ],
                $queryTemplate
            );
        };

        // -------------------------------------------------------------
        // 4️⃣ — Ejecutar contra Shopify
        // -------------------------------------------------------------

        $response = $this->graphql($queryBuilder($cursor));


        // Log::info($result['items'] ?? []);
        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress']
        );

        return [
            'orders'     => json_decode(json_encode($result['items'])),
            'pageInfo'   => $result['pageInfo'] ?? null,
            'lastCursor' => $result['lastCursor'] ?? null,
        ];

        // return $result;
    }

    public function getOrdersByTagBetween($tag, $start = null, $end = null): array
    {
        Log::info($start);
        Log::info($end);

        $start = $start ?? date('Y-m-d');
        $end = $end ?? date('Y-m-d', strtotime('+1 month', strtotime($start)));

        Log::info('getOrdersSearch');

        // -------------------------------------------------------------
        // 1️⃣ — Sub-queries para evitar repetición
        // -------------------------------------------------------------
        $shippingLinesQuery   = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery        = $this->customerQuery();
        $itemsQuery           = $this->itemsQuery();
        $eventsQuery          = $this->eventsQuery();

        // -------------------------------------------------------------
        // 2️⃣ — GraphQL Template
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
        {
            orders(
                first: 50,
                sortKey: CREATED_AT,
                reverse: true,
                query: ":searchQuery financial_status:paid cancelled_at:null fulfillment_status:fulfilled created_at:>=:start created_at:<=:end",
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
                        updatedAt
                        tags
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
    
                        $itemsQuery
                        $shippingLinesQuery
                    }
                }
            }
        }
        GRAPHQL;

        // -------------------------------------------------------------
        // 3️⃣ — QueryBuilder
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use (
            $queryTemplate,
            $start,
            $end,
            $tag
        ) {

            $searchQuery = "(tag:$tag OR note:$tag)";

            return str_replace(
                [':start', ':end', ':searchQuery', ':cursor'],
                [
                    $start,
                    $end,
                    $searchQuery,
                    $cursor ? "\"$cursor\"" : 'null'
                ],
                $queryTemplate
            );
        };

        // -------------------------------------------------------------
        // 4️⃣ — Ejecutar query
        // -------------------------------------------------------------
        $result = $this->getDataFromShopify(
            'orders',
            $queryBuilder,
            ['lineItems', 'shippingLines']
        );

        // -------------------------------------------------------------
        // 5️⃣ — Logs
        // -------------------------------------------------------------
        Log::info('result de getOrdersByTagBetween');
        Log::info($result);

        return $result;
    }

    public function getSearchOrders(int $limit = 20, $cursor = null, $search = null): array
    {
        Log::info('getSearchOrders');

        // Asegurar límite mínimo
        $limit = max(1, $limit);

        // Normalizar búsqueda
        $search = trim($search ?? '');

        // -------------------------------------------------------------
        // 1️⃣ — Construcción dinámica del filtro de Shopify
        // -------------------------------------------------------------
        // Si busca por nombre del cliente o del pedido
        // customer_name:*Maria*     (cliente)
        // name:#1001                (número de orden)
        // note:*texto*              (nota del pedido)
        // email:*gmail*             (correo)
        // etc.
        // $searchFilter = $search !== ''
        //     ? " (customer_name:*{$search}* OR name:*{$search}* OR note:*{$search}*)"
        //     : '';


        $searchFilter = '';

        if ($search !== '') {
            $searchFilter = " customer_name:*{$search}* OR name:*{$search}* OR note:*{$search}*";
        }

        $queryFilter = "financial_status:paid cancelled_at:null{$searchFilter}";

        // -------------------------------------------------------------
        // 2️⃣ — Sub-queries
        // -------------------------------------------------------------
        $shippingLinesQuery   = $this->shippingLinesQuery();
        $shippingAddressQuery = $this->shippingAddressQuery();
        $customerQuery        = $this->customerQuery();
        $itemsQuery           = $this->itemsQuery();
        $eventsQuery          = $this->eventsQuery();

        // -------------------------------------------------------------
        // 3️⃣ — GraphQL Template con placeholders
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
    {
        orders(
            first: :limit,
            sortKey: CREATED_AT,
            reverse: true,
            query: :queryFilter,
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
                    $shippingLinesQuery
                    $shippingAddressQuery
                    $customerQuery
                    $itemsQuery
                    $eventsQuery
                }
            }
        }
    }
    GRAPHQL;

        // -------------------------------------------------------------
        // 4️⃣ — Builder (limit, cursor, filtro)
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use ($queryTemplate, $limit, $queryFilter) {
            return str_replace(
                [':limit', ':cursor', ':queryFilter'],
                [
                    $limit,
                    $cursor ? "\"$cursor\"" : 'null',
                    $queryFilter
                ],
                $queryTemplate
            );
        };

        // -------------------------------------------------------------
        // 5️⃣ — Ejecutar contra Shopify
        // -------------------------------------------------------------
        $response = $this->graphql($queryBuilder($cursor));

        // -------------------------------------------------------------
        // 6️⃣ — Normalizar datos
        // -------------------------------------------------------------
        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'orders',
            ['lineItems', 'fulfillments', 'customer', 'events', 'shippingLines', 'shippingAddress']
        );

        return [
            'orders'     => json_decode(json_encode($result['items'])),
            'pageInfo'   => $result['pageInfo'] ?? null,
            'lastCursor' => $result['lastCursor'] ?? null,
        ];
    }

    public function create($input)
    {

        $query = <<<GRAPHQL
                    mutation orderCreate($input: OrderInput!) {
                        orderCreate(input: $input) {
                            order {
                            id
                            name
                            displayFinancialStatus
                            displayFulfillmentStatus
                            totalPriceSet {
                                shopMoney {
                                amount
                                currencyCode
                                }
                            }
                            }
                            userErrors {
                            field
                            message
                            }
                        }
                        }
                    }
                GRAPHQL;
    }
}
