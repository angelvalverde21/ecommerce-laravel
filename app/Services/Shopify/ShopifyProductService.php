<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopifyProductService extends ShopifyBaseService
{

    public function getProducts(int $limit = 10, ?string $searchTerm = null, ?string $cursor = null): array
    {
        // --- Construcción del query ---
        $filters = ['status:active']; // <-- FILTRO OBLIGATORIO

        if ($searchTerm) {
            $filters[] = $searchTerm; // añade tu búsqueda adicional
        }

        // Une todos los filtros con AND
        $queryText = implode(' AND ', $filters);

        // json_encode asegura que GraphQL reciba "texto"
        $queryValue  = json_encode($queryText);
        $cursorValue = $cursor ? json_encode($cursor) : "null";

        $query = <<<GRAPHQL
                    {
                        products(
                            first: $limit,
                            after: $cursorValue,
                            sortKey: CREATED_AT,
                            reverse: true,
                            query: $queryValue
                        ) {
                            edges {
                                cursor
                                node {
                                    id
                                    title
                                    bodyHtml
                                    vendor
                                    productType
                                    handle
                                    createdAt
                                    updatedAt
                                    onlineStoreUrl
                                    status
                                    tags
                                    category {
                                    id
                                    name
                                    fullName
                                    }
                                    variants(first: 10) {
                                        edges {
                                            node {
                                            id
                                            title
                                            price
                                            inventoryQuantity
                                            }
                                        }
                                    }
                                    options {
                                        id
                                        name
                                        values
                                    }
                                    images(first: 10) {
                                        edges {
                                            node {
                                            id
                                            src
                                            }
                                        }
                                    }
                                    featuredImage {
                                        id
                                        src
                                    }
                                }
                            }
                            pageInfo {
                                hasNextPage
                                hasPreviousPage
                            }
                        }
                    }
                    GRAPHQL;

        // Shopify recibe solo el query final
        $response = $this->graphql($query)->json();

        $result = GraphQLResponseHelper::normalizeEntity(
            $response,
            'products', //puede ser products pero le pondremos data para estandarizar
            ['variants', 'images']
        );

        return [
            'data'     => json_decode(json_encode($result)),
            // 'pageInfo'   => $result['pageInfo'] ?? null,
            // 'lastCursor' => $result['lastCursor'] ?? null,
        ];
    }

    public function sync(): array
    {
        //

        // Crear rango de fechas

        // -------------------------------------------------------------
        // 1️⃣ — EL QUERY ESTÁ AQUÍ MISMO (plantilla)
        // -------------------------------------------------------------

        $queryTemplate = <<<GRAPHQL
                    {
                        products(
                            first: 100,
                            after: :cursor,
                            sortKey: CREATED_AT,
                            reverse: true
                        ) {
                            edges {
                                cursor
                                node {
                                    id
                                    title
                                    bodyHtml
                                    vendor
                                    productType
                                    handle
                                    createdAt
                                    updatedAt
                                    onlineStoreUrl
                                    status
                                    tags
                                    category {
                                        id
                                        name
                                        fullName
                                    }
                                    variants(first: 10) {
                                        edges {
                                            node {
                                            id
                                            title
                                            price
                                            inventoryQuantity
                                            }
                                        }
                                    }
                                    options {
                                        id
                                        name
                                        values
                                    }
                                    images(first: 10) {
                                        edges {
                                            node {
                                            id
                                            src
                                            }
                                        }
                                    }
                                    featuredImage {
                                        id
                                        src
                                    }
                                }
                            }
                            pageInfo {
                                hasNextPage
                                hasPreviousPage,
                                endCursor
                            }
                        }
                    }
                    GRAPHQL;

        // -------------------------------------------------------------
        // 2️⃣ — QueryBuilder para reemplazar placeholders
        // -------------------------------------------------------------
        $queryBuilder = function (?string $cursor) use ($queryTemplate) {
            return str_replace(
                [':cursor'], //Elementos a reeemplazar
                [ $cursor ? "\"$cursor\"" : 'null'], //Con estos valores
                $queryTemplate //En el template
            );
        };

        // -------------------------------------------------------------
        // 3️⃣ — Ejecutar el query builder ($queryBuilder) para traer los datos de Shopify
        // -------------------------------------------------------------
        $result = $this->getDataFromShopify(
            'products',
            $queryBuilder,
            ['variants','images'] //podria ser 'lineItems'
        );

        // -------------------------------------------------------------
        // 4️⃣ — Normalizar items
        // -------------------------------------------------------------
        $products = collect($result['items'] ?? []); //items es como devuelve getDataFromShopify

        if ($products->isEmpty()) {
            Log::warning('Products: vacío');
        }

        Log::info($products);

        return [
            'data' => json_decode(json_encode($products)),
            // 'pageInfo'   => $result['pageInfo'] ?? null,
            // 'lastCursor' => $result['lastCursor'] ?? null,
        ];
        // Convertir fechas a local

        // Agrupar por fecha

        // Construir el periodo completo
    }
}
