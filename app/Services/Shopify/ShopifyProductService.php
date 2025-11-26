<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Services\Shopify\ShopifyBaseService;
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
}
