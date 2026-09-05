<?php

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Log;

class FulfillmentService extends ShopifyBaseService
{
    /**
     * Marca un fulfillment existente como SUCCESS
     * 
     * @param string $fulfillmentId ID del fulfillment (GID o numérico)
     * @param array|null $trackingInfo Información de seguimiento (opcional)
     * @param bool $notifyCustomer Notificar al cliente (opcional, default: true)
     * @return array
     */
    public function markFulfillmentAsSuccess(
        string $fulfillmentId,
        ?array $trackingInfo = null,
        bool $notifyCustomer = true
    ): array {
        // VALIDAR ID
        if (!$this->isValidGid($fulfillmentId, 'Fulfillment')) {
            if (is_numeric($fulfillmentId)) {
                $fulfillmentId = $this->toGid($fulfillmentId, 'Fulfillment');
            } else {
                Log::error('Invalid fulfillment ID', ['id' => $fulfillmentId]);
                return [
                    'success' => false,
                    'error' => 'Invalid fulfillment ID format'
                ];
            }
        }

        // VERIFICAR QUE EL FULFILLMENT EXISTA
        $fulfillmentExists = $this->checkIfFulfillmentExists($fulfillmentId);

        if (!$fulfillmentExists) {
            return [
                'success' => false,
                'error' => 'Fulfillment not found',
                'fulfillment_id' => $fulfillmentId
            ];
        }

        // ACTUALIZAR A SUCCESS
        return $this->updateFulfillmentToSuccess(
            $fulfillmentId,
            $trackingInfo,
            $notifyCustomer
        );
    }

    /**
     * Crea un fulfillment desde un Fulfillment Order y lo marca como SUCCESS
     * 
     * @param string $fulfillmentOrderId ID del Fulfillment Order (GID o numérico)
     * @param array|null $trackingInfo Información de seguimiento (opcional)
     * @param bool $notifyCustomer Notificar al cliente (opcional, default: true)
     * @param array|null $lineItems Items específicos a enviar (opcional)
     * @return array
     */
    public function createFulfillmentFromOrder(
        string $fulfillmentOrderId,
        ?array $trackingInfo = null,
        bool $notifyCustomer = true,
        ?array $lineItems = null
    ): array {
        // 1. VALIDAR FULFILLMENT ORDER ID
        if (!$this->isValidGid($fulfillmentOrderId, 'FulfillmentOrder')) {
            if (is_numeric($fulfillmentOrderId)) {
                $fulfillmentOrderId = $this->toGid($fulfillmentOrderId, 'FulfillmentOrder');
            } else {
                Log::error('Invalid fulfillment order ID', ['id' => $fulfillmentOrderId]);
                return [
                    'success' => false,
                    'error' => 'Invalid fulfillment order ID format'
                ];
            }
        }

        // 2. OBTENER EL FULFILLMENT ORDER
        $fulfillmentOrder = $this->getFulfillmentOrder($fulfillmentOrderId);

        if (!$fulfillmentOrder) {
            return [
                'success' => false,
                'error' => 'Fulfillment Order not found',
                'fulfillment_order_id' => $fulfillmentOrderId
            ];
        }

        // 3. VERIFICAR QUE ESTÉ OPEN
        if (strtolower($fulfillmentOrder['status']) !== 'open') {
            return [
                'success' => false,
                'error' => "Fulfillment Order is not OPEN (status: {$fulfillmentOrder['status']})",
                'fulfillment_order_id' => $fulfillmentOrderId,
                'current_status' => $fulfillmentOrder['status']
            ];
        }

        // 4. VERIFICAR SI YA TIENE FULFILLMENTS
        $existingFulfillments = $this->getFulfillmentsFromOrder($fulfillmentOrder);

        if (!empty($existingFulfillments)) {
            // ✅ YA TIENE FULFILLMENTS → Verificar cuáles están OPEN
            $openFulfillments = array_filter($existingFulfillments, function ($fulfillment) {
                return strtolower($fulfillment['status']) === 'open';
            });

            if (!empty($openFulfillments)) {
                // Tiene fulfillments OPEN → Actualizar el primero o todos
                // Opción 1: Actualizar solo el primer OPEN
                $firstOpen = reset($openFulfillments);
                return $this->markFulfillmentAsSuccess(
                    $firstOpen['id'],
                    $trackingInfo,
                    $notifyCustomer
                );

                // Opción 2: Actualizar TODOS los OPEN (descomentar si se necesita)
                /*
                $results = [];
                foreach ($openFulfillments as $fulfillment) {
                    $results[] = $this->markFulfillmentAsSuccess(
                        $fulfillment['id'],
                        $trackingInfo,
                        $notifyCustomer
                    );
                }
                return [
                    'success' => true,
                    'action' => 'updated_multiple',
                    'results' => $results
                ];
                */
            }

            // Todos los fulfillments están en estado final (SUCCESS, FAILURE, CANCELLED)
            return [
                'success' => false,
                'error' => 'All fulfillments are already in final status',
                'fulfillment_order_id' => $fulfillmentOrderId,
                'existing_fulfillments' => $existingFulfillments
            ];
        }

        // 5. NO TIENE FULFILLMENTS → Crear uno nuevo
        return $this->createFulfillmentFromOrderMutation(
            $fulfillmentOrderId,
            $trackingInfo,
            $notifyCustomer,
            $lineItems
        );
    }

    /**
     * Marca una orden como completamente enviada (maneja ambos casos)
     * 
     * @param string $orderId ID de la orden (GID o numérico)
     * @param array|null $trackingInfo Información de seguimiento (opcional)
     * @param bool $notifyCustomer Notificar al cliente (opcional)
     * @param array|null $lineItems Items específicos a enviar (opcional)
     * @return array
     */
    public function markOrderAsFulfilled(
        string $orderId,
        ?array $trackingInfo = null,
        bool $notifyCustomer = true,
        ?array $lineItems = null
    ): array {
        // 1. OBTENER LOS FULFILLMENT ORDERS DE LA ORDEN
        $orderData = $this->getOrderFulfillmentOrders($orderId);

        if (!$orderData['success']) {
            return $orderData;
        }

        $fulfillmentOrders = $orderData['fulfillment_orders'];
        $order = $orderData['order'];

        if (empty($fulfillmentOrders)) {
            return [
                'success' => false,
                'error' => 'Order has no fulfillment orders',
                'order_id' => $orderId,
                'order_name' => $order['name'] ?? 'N/A'
            ];
        }

        // 2. FILTRAR FULFILLMENT ORDERS EN ESTADO OPEN
        $openFulfillmentOrders = array_filter($fulfillmentOrders, function ($fo) {
            return strtolower($fo['status']) === 'open';
        });

        if (empty($openFulfillmentOrders)) {
            return [
                'success' => true,
                'message' => 'Order has no open fulfillment orders',
                'order_id' => $orderId,
                'order_name' => $order['name'] ?? 'N/A',
                'fulfillment_orders' => $fulfillmentOrders
            ];
        }

        // 3. PROCESAR CADA FULFILLMENT ORDER
        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($openFulfillmentOrders as $fo) {
            // Obtener fulfillments existentes
            $existingFulfillments = $this->getFulfillmentsFromOrder($fo);

            if (!empty($existingFulfillments)) {
                // ✅ YA TIENE FULFILLMENTS → Actualizar los OPEN
                $openFulfillments = array_filter($existingFulfillments, function ($fulfillment) {
                    return strtolower($fulfillment['status']) === 'open';
                });

                if (!empty($openFulfillments)) {
                    // Actualizar cada fulfillment OPEN
                    foreach ($openFulfillments as $fulfillment) {
                        $result = $this->markFulfillmentAsSuccess(
                            $fulfillment['id'],
                            $trackingInfo,
                            $notifyCustomer
                        );

                        $results[] = [
                            'fulfillment_order_id' => $fo['id'],
                            'fulfillment_id' => $fulfillment['id'],
                            'action' => 'updated',
                            'success' => $result['success'],
                            'error' => $result['error'] ?? null,
                            'fulfillment' => $result['fulfillment'] ?? null
                        ];

                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $failedCount++;
                        }
                    }
                } else {
                    // No hay fulfillments OPEN
                    $results[] = [
                        'fulfillment_order_id' => $fo['id'],
                        'action' => 'skipped',
                        'success' => true,
                        'message' => 'No open fulfillments to update'
                    ];
                }
            } else {
                // ❌ NO TIENE FULFILLMENTS → Crear uno nuevo
                $result = $this->createFulfillmentFromOrder(
                    $fo['id'],
                    $trackingInfo,
                    $notifyCustomer,
                    $lineItems
                );

                $results[] = [
                    'fulfillment_order_id' => $fo['id'],
                    'action' => 'created',
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null,
                    'fulfillment' => $result['fulfillment'] ?? null
                ];

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        return [
            'success' => $failedCount === 0,
            'order_id' => $orderId,
            'order_name' => $order['name'] ?? 'N/A',
            'total_fulfillment_orders' => count($fulfillmentOrders),
            'open_fulfillment_orders' => count($openFulfillmentOrders),
            'updated' => $successCount,
            'failed' => $failedCount,
            'has_tracking' => !empty($trackingInfo),
            'notify_customer' => $notifyCustomer,
            'results' => $results,
            'message' => $failedCount === 0
                ? 'Order fulfilled successfully'
                : 'Some fulfillment orders could not be fulfilled'
        ];
    }

    /**
     * Obtiene todos los fulfillments de un Fulfillment Order
     * 
     * @param array $fulfillmentOrder
     * @return array
     */
    private function getFulfillmentsFromOrder(array $fulfillmentOrder): array
    {
        $fulfillments = [];

        if (isset($fulfillmentOrder['fulfillments']['edges']) && !empty($fulfillmentOrder['fulfillments']['edges'])) {
            $fulfillments = array_map(function ($edge) {
                return $edge['node'];
            }, $fulfillmentOrder['fulfillments']['edges']);
        }

        return $fulfillments;
    }

    /**
     * Verifica si un fulfillment existe
     */
    private function checkIfFulfillmentExists(string $fulfillmentId): bool
    {
        $query = <<<GQL
            query checkFulfillment(\$id: ID!) {
                node(id: \$id) {
                    __typename
                }
            }
        GQL;

        $response = $this->graphql($query, ['id' => $fulfillmentId]);
        $data = $response->json();

        if (isset($data['errors']) || !isset($data['data']['node'])) {
            return false;
        }

        return $data['data']['node']['__typename'] === 'Fulfillment';
    }

    /**
     * Actualiza un fulfillment existente a SUCCESS
     */
    private function updateFulfillmentToSuccess(
        string $fulfillmentId,
        ?array $trackingInfo = null,
        bool $notifyCustomer = true
    ): array {
        $fulfillmentData = [
            'status' => 'SUCCESS',
            'notifyCustomer' => $notifyCustomer
        ];

        if ($trackingInfo !== null && !empty($trackingInfo)) {
            $fulfillmentData['trackingInfo'] = $trackingInfo;
        }

        $mutation = <<<GQL
            mutation fulfillmentUpdate(\$id: ID!, \$fulfillment: FulfillmentUpdateInput!) {
                fulfillmentUpdate(id: \$id, fulfillment: \$fulfillment) {
                    fulfillment {
                        id
                        status
                        trackingInfo {
                            number
                            url
                            company
                        }
                        updatedAt
                        order {
                            id
                            name
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $fulfillmentId,
            'fulfillment' => $fulfillmentData
        ];

        Log::info('Updating existing fulfillment to SUCCESS', [
            'fulfillment_id' => $fulfillmentId,
            'has_tracking' => !empty($trackingInfo)
        ]);

        $response = $this->graphql($mutation, $variables);
        $data = $response->json();

        if (isset($data['errors'])) {
            return [
                'success' => false,
                'error' => $data['errors']
            ];
        }

        if (!empty($data['data']['fulfillmentUpdate']['userErrors'])) {
            return [
                'success' => false,
                'error' => $data['data']['fulfillmentUpdate']['userErrors']
            ];
        }

        $fulfillment = $data['data']['fulfillmentUpdate']['fulfillment'] ?? null;

        return [
            'success' => true,
            'fulfillment' => $fulfillment,
            'action' => 'updated'
        ];
    }

    /**
     * Crea un fulfillment desde un Fulfillment Order (mutación)
     */
    private function createFulfillmentFromOrderMutation(
        string $fulfillmentOrderId,
        ?array $trackingInfo = null,
        bool $notifyCustomer = true,
        ?array $lineItems = null
    ): array {
        $fulfillmentOrderData = [
            'fulfillmentOrderId' => $fulfillmentOrderId
        ];

        if ($lineItems !== null && !empty($lineItems)) {
            $fulfillmentOrderData['fulfillmentOrderLineItems'] = $lineItems;
        }

        $fulfillmentData = [
            'lineItemsByFulfillmentOrder' => [
                $fulfillmentOrderData
            ],
            'notifyCustomer' => $notifyCustomer
        ];

        if ($trackingInfo !== null && !empty($trackingInfo)) {
            $fulfillmentData['trackingInfo'] = $trackingInfo;
        }

        $mutation = <<<GQL
            mutation fulfillmentCreate(\$fulfillment: FulfillmentInput!) {
                fulfillmentCreate(fulfillment: \$fulfillment) {
                    fulfillment {
                        id
                        status
                        trackingInfo {
                            number
                            url
                            company
                        }
                        createdAt
                        updatedAt
                        order {
                            id
                            name
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'fulfillment' => $fulfillmentData
        ];

        Log::info('Creating new fulfillment from Fulfillment Order', [
            'fulfillment_order_id' => $fulfillmentOrderId,
            'has_tracking' => !empty($trackingInfo),
            'notify_customer' => $notifyCustomer,
            'has_line_items' => !empty($lineItems)
        ]);

        $response = $this->graphql($mutation, $variables);
        $data = $response->json();

        if (isset($data['errors'])) {
            return [
                'success' => false,
                'error' => $data['errors']
            ];
        }

        if (!empty($data['data']['fulfillmentCreate']['userErrors'])) {
            return [
                'success' => false,
                'error' => $data['data']['fulfillmentCreate']['userErrors']
            ];
        }

        $fulfillment = $data['data']['fulfillmentCreate']['fulfillment'] ?? null;

        return [
            'success' => true,
            'fulfillment' => $fulfillment,
            'action' => 'created'
        ];
    }

    /**
     * Obtiene un Fulfillment Order por ID
     */
    public function getFulfillmentOrder(string $fulfillmentOrderId): ?array
    {
        if (!$this->isValidGid($fulfillmentOrderId, 'FulfillmentOrder')) {
            if (is_numeric($fulfillmentOrderId)) {
                $fulfillmentOrderId = $this->toGid($fulfillmentOrderId, 'FulfillmentOrder');
            } else {
                return null;
            }
        }

        $query = <<<GQL
            query getFulfillmentOrder(\$id: ID!) {
                node(id: \$id) {
                    ... on FulfillmentOrder {
                        id
                        status
                        order {
                            id
                            name
                        }
                        assignedLocation {
                            name
                        }
                        lineItems(first: 50) {
                            edges {
                                node {
                                    id
                                    title
                                    quantity
                                    sku
                                    fulfillableQuantity
                                }
                            }
                        }
                        fulfillments(first: 50) {
                            edges {
                                node {
                                    id
                                    status
                                    trackingInfo {
                                        number
                                        url
                                        company
                                    }
                                    createdAt
                                    updatedAt
                                }
                            }
                        }
                    }
                }
            }
        GQL;

        $response = $this->graphql($query, ['id' => $fulfillmentOrderId]);
        $data = $response->json();

        if (isset($data['errors']) || !isset($data['data']['node'])) {
            return null;
        }

        return $data['data']['node'];
    }

    /**
     * Obtiene los Fulfillment Orders de una orden
     */
    public function getOrderFulfillmentOrders(string $orderId): array
    {
        if (!$this->isValidGid($orderId, 'Order')) {
            if (is_numeric($orderId)) {
                $orderId = $this->toGid($orderId, 'Order');
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid order ID format'
                ];
            }
        }

        $query = <<<GQL
            query getOrderFulfillmentOrders(\$id: ID!) {
                order(id: \$id) {
                    id
                    name
                    fulfillmentOrders(first: 50) {
                        edges {
                            node {
                                id
                                status
                                assignedLocation {
                                    name
                                }
                                lineItems(first: 50) {
                                    edges {
                                        node {
                                            id
                                            title
                                            quantity
                                            fulfillableQuantity
                                        }
                                    }
                                }
                                fulfillments(first: 50) {
                                    edges {
                                        node {
                                            id
                                            status
                                            trackingInfo {
                                                number
                                                url
                                                company
                                            }
                                            createdAt
                                            updatedAt
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        GQL;

        $response = $this->graphql($query, ['id' => $orderId]);
        $data = $response->json();

        if (isset($data['errors']) || !isset($data['data']['order'])) {
            return [
                'success' => false,
                'error' => $data['errors'] ?? 'Order not found'
            ];
        }

        $edges = $data['data']['order']['fulfillmentOrders']['edges'] ?? [];
        $fulfillmentOrders = array_map(function ($edge) {
            return $edge['node'];
        }, $edges);

        return [
            'success' => true,
            'order' => $data['data']['order'],
            'fulfillment_orders' => $fulfillmentOrders
        ];
    }

    /**
     * Método helper para formatear tracking info
     */
    public function formatTrackingInfo(
        string $number,
        ?string $url = null,
        ?string $company = null
    ): array {
        return [
            [
                'number' => $number,
                'url' => $url ?? '',
                'company' => $company ?? ''
            ]
        ];
    }

    /**
     * Valida si un ID es un GID válido
     */
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
     * Convierte ID numérico a GID
     */
    public function toGid(string $numericId, string $resourceType = 'Order'): string
    {
        return "gid://shopify/{$resourceType}/{$numericId}";
    }
}
