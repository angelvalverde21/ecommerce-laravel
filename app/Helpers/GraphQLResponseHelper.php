<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class GraphQLResponseHelper
{
    /**
     * Normaliza una respuesta GraphQL con estructura edges/nodes
     *
     * @param mixed $response Response de HTTP client o array
     * @param string $dataPath Ruta al campo principal (ej: 'data.products')
     * @param array $nestedFields Campos anidados con estructura edges/nodes ['variants', 'images']
     * @param callable|null $transformer Función adicional para transformar cada nodo
     * @return array
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

            // Normalizar campos anidados
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
            ? data_get($response, "{$dataPath}.pageInfo")
            : $response->json("{$dataPath}.pageInfo");

        return [
            'items'      => $items,
            'pageInfo'   => $pageInfo,
            'lastCursor' => $items ? end($items)['cursor'] : null,
        ];
    }

    /**
     * Versión simplificada para un solo nivel de anidación
     *
     * @param mixed $response
     * @param string $entityName Nombre de la entidad (ej: 'products', 'orders')
     * @param array $nestedFields
     * @param callable|null $transformer
     * @return array
     */
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
}