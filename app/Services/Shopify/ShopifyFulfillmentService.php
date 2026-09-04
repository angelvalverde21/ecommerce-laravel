<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

//Servicio de cumplimiento
class ShopifyFulfillmentService extends ShopifyBaseService
{

public function markAsFulfilled(
        string $fulfillmentOrderId,
        array $trackingInfo = [],
        bool $notifyCustomer = false,
        ?string $message = null
    ): array {
        
        // Validar que el ID sea un GID válido
        if (!$this->isValidGid($fulfillmentOrderId, 'FulfillmentOrder')) {
            Log::error('Invalid FulfillmentOrder GID', ['gid' => $fulfillmentOrderId]);
            return [
                'success' => false,
                'error' => 'Invalid FulfillmentOrder ID format'
            ];
        }

        $mutation = '
            mutation fulfillmentCreateV2($fulfillment: FulfillmentV2Input!) {
                fulfillmentCreateV2(fulfillment: $fulfillment) {
                    fulfillment {
                        id
                        createdAt
                        displayStatus
                        trackingInfo {
                            number
                            url
                            company
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $variables = [
            'fulfillment' => [
                'fulfillmentOrderId' => $fulfillmentOrderId,
                'notifyCustomer' => $notifyCustomer,
            ]
        ];

        // Agregar tracking si existe
        if (!empty($trackingInfo)) {
            $variables['fulfillment']['trackingInfo'] = [$trackingInfo];
        }

        // Agregar mensaje personalizado
        if ($message) {
            $variables['fulfillment']['message'] = $message;
        }

        Log::info('Marcando fulfillment como preparado', [
            'fulfillment_order_id' => $fulfillmentOrderId,
            'tracking' => $trackingInfo
        ]);

        $response = $this->graphql($mutation, $variables);

        // Log de la respuesta
        Log::info('Respuesta de Shopify', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        // Verificar errores de Shopify
        if (!$response->successful()) {
            Log::error('Error en API de Shopify', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error al conectar con Shopify',
                'status' => $response->status()
            ];
        }

        $data = $response->json();

        // Verificar errores en la mutación
        if (!empty($data['data']['fulfillmentCreateV2']['userErrors'])) {
            $errors = $data['data']['fulfillmentCreateV2']['userErrors'];
            Log::error('Errores en fulfillmentCreateV2', ['errors' => $errors]);
            
            return [
                'success' => false,
                'error' => $errors[0]['message'] ?? 'Error desconocido',
                'errors' => $errors
            ];
        }

        // Éxito
        $fulfillment = $data['data']['fulfillmentCreateV2']['fulfillment'] ?? null;
        
        if (!$fulfillment) {
            Log::error('No se recibió fulfillment en la respuesta');
            return [
                'success' => false,
                'error' => 'No se pudo crear la fulfillment'
            ];
        }

        Log::info('Fulfillment creada exitosamente', [
            'fulfillment_id' => $fulfillment['id'],
            'status' => $fulfillment['displayStatus']
        ]);

        return [
            'success' => true,
            'fulfillment' => $fulfillment,
            'fulfillment_id' => $fulfillment['id']
        ];
    }

}