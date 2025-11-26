<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class GraphQLResponseHelper
{

    //Normaliza una estructura GraphQL para una entidad específica
    //data->orders->edges (plural)

    public static function normalizeEntity(
        $response,
        string $entityName,
        array $nestedFields = [],
        ?callable $transformer = null
    ): array {
        return self::normalize(
            $response,
            "data.{$entityName}",
            $nestedFields,
            $transformer
        );
    }

    /**
     * Normaliza una respuesta GraphQL con estructura edges/nodes
     *
    */
    public static function normalize(
        $response,
        string $dataPath,
        array $nestedFields = [],
        ?callable $transformer = null
    ): array {
        // Obtener edges
        $edges = is_array($response)
            ? data_get($response, "{$dataPath}.edges", [])
            : $response->json("{$dataPath}.edges") ?? [];

        // Procesar items
        $items = collect($edges)->map(function ($edge) use ($nestedFields, $transformer) {
            $node = $edge['node'];

            // Normalizar campos anidados (ej: variants, images)
            foreach ($nestedFields as $field) {
                if (isset($node[$field]['edges'])) {
                    $node[$field] = collect($node[$field]['edges'])
                        ->map(fn($e) => $e['node'])
                        ->toArray();
                }
            }

            // Agregar cursor al nodo
            $node['cursor'] = $edge['cursor'];

            // Aplicar transformación personalizada
            if ($transformer) {
                $node = $transformer($node);
            }

            return $node;
        })->toArray();

        // Obtener pageInfo
        $pageInfo = is_array($response)
            ? data_get($response, "{$dataPath}.pageInfo", [])
            : $response->json("{$dataPath}.pageInfo") ?? [];

        // Extraer info de paginación
        $hasNextPage = $pageInfo['hasNextPage'] ?? false;
        $endCursor   = $pageInfo['endCursor'] ?? null;

        return [
            'items'       => $items,
            'pageInfo'    => $pageInfo,
            'hasNextPage' => $hasNextPage,
            'cursor'      => $endCursor,
            'lastCursor'  => $items ? end($items)['cursor'] : null,
        ];
    }



    /**
     * Versión simplificada para un solo nivel de anidación
     * Normaliza una respuesta GraphQL que contiene una sola orden (Shopify)
     * data->orders->edges[0] (singular)
     */
    public static function normalizeSingle($response, string $entityName = 'orders', array $nestedFields = []): ?array
    {
        // Obtener nodo único (primer item de edges)
        $node = is_array($response)
            ? data_get($response, "data.{$entityName}.edges.0.node")
            : $response->json("data.{$entityName}.edges.0.node");

        if (!$node) {
            return null;
        }

        // Aplanar campos tipo edges→node (ej. lineItems, shippingLines, fulfillments, events, etc.)
        foreach ($nestedFields as $field) {
            if (isset($node[$field]['edges'])) {
                $node[$field] = collect($node[$field]['edges'])
                    ->map(fn($edge) => $edge['node'])
                    ->toArray();
            }
        }

        return $node;
    }
}
