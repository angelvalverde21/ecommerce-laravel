<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class GraphQLResponseHelper
{

    //Normaliza una estructura GraphQL para una entidad específica
    //data->orders->edges (plural)

    /**
     * Normaliza una entidad específica de una respuesta GraphQL
     * 
     * @param array|Response $response Respuesta de Shopify
     * @param string $entityName Nombre de la entidad (ej: 'orders', 'products')
     * @param array $nestedFields OPCIONAL - Ya no se usa, se normaliza todo automáticamente
     * @param callable|null $transformer Transformador personalizado
     * @return array Datos normalizados
     */
    public static function normalizeEntity(
        $response,
        string $entityName,
        array $nestedFields = [],  // ← Se mantiene pero no se usa
        ?callable $transformer = null
    ): array {
        return self::normalize(
            $response,
            "data.{$entityName}",
            $nestedFields,  // ← Se pasa pero se ignora
            $transformer
        );
    }

    /**
     * Normaliza una respuesta GraphQL con estructura edges/nodes
     * 
     * @param array|Response $response Respuesta de Shopify
     * @param string $dataPath Ruta a los datos (ej: 'data.orders')
     * @param array $nestedFields OPCIONAL - Ya no se usa, se normaliza todo automáticamente
     * @param callable|null $transformer Transformador personalizado
     * @return array Datos normalizados
     */
    public static function normalize(
        $response,
        string $dataPath,
        array $nestedFields = [],  // ← Se mantiene pero no se usa
        ?callable $transformer = null
    ): array {
        // Obtener edges
        $edges = is_array($response)
            ? data_get($response, "{$dataPath}.edges", [])
            : $response->json("{$dataPath}.edges") ?? [];

        // Procesar items con normalización recursiva automática
        $items = collect($edges)->map(function ($edge) use ($transformer) {
            $node = $edge['node'];

            // 🔥 NORMALIZAR TODO RECURSIVAMENTE
            $node = self::normalizeAllNodes($node);

            // Agregar cursor al nodo
            $node['cursor'] = $edge['cursor'];

            // Aplicar transformación personalizada si existe
            if ($transformer) {
                $node = $transformer($node);
            }

            return $node;
        })->toArray();

        // Obtener pageInfo
        $pageInfo = is_array($response)
            ? data_get($response, "{$dataPath}.pageInfo", [])
            : $response->json("{$dataPath}.pageInfo") ?? [];

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
     * Normaliza TODAS las estructuras edges/nodes recursivamente
     * 
     * @param mixed $data Datos a normalizar
     * @return mixed Datos normalizados
     */
    protected static function normalizeAllNodes($data)
    {
        // Si es un array, procesarlo
        if (is_array($data)) {
            // Si tiene 'edges', normalizarlo (estructura GraphQL)
            if (isset($data['edges']) && is_array($data['edges'])) {
                $normalized = collect($data['edges'])->map(function ($edge) {
                    // Si el edge tiene 'node', procesarlo
                    if (isset($edge['node'])) {
                        $node = self::normalizeAllNodes($edge['node']);
                        // Agregar cursor si existe
                        if (isset($edge['cursor'])) {
                            $node['cursor'] = $edge['cursor'];
                        }
                        return $node;
                    }
                    // Si el edge no tiene 'node', procesar el edge completo
                    return self::normalizeAllNodes($edge);
                })->toArray();

                return $normalized;
            }

            // Si NO tiene 'edges', procesar cada elemento del array
            return array_map(function ($value) {
                return self::normalizeAllNodes($value);
            }, $data);
        }

        // Si no es array, devolver el valor original
        return $data;
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

    // public static function normalizeSingle(
    //     $response,
    //     string $dataPath,
    //     array $nestedFields = [],
    //     ?callable $transformer = null
    // ): ?array {

    //     // Obtener el nodo directamente
    //     $node = is_array($response)
    //         ? data_get($response, $dataPath)
    //         : $response->json($dataPath);

    //     if (!$node) {
    //         return null;
    //     }

    //     // Normalizar campos anidados (ej: lineItems, fulfillments, etc)
    //     foreach ($nestedFields as $field) {
    //         if (isset($node[$field]['edges'])) {
    //             $node[$field] = collect($node[$field]['edges'])
    //                 ->map(fn($e) => $e['node'])
    //                 ->toArray();
    //         }
    //     }

    //     // Aplicar transformación personalizada
    //     if ($transformer) {
    //         $node = $transformer($node);
    //     }

    //     return $node;
    // }
}
