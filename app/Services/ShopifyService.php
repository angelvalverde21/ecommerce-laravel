<?php

namespace App\Services;

use App\Helpers\GraphQLResponseHelper;
use Illuminate\Support\Facades\Http;

class ShopifyService
{
  protected string $baseUrl;
  protected string $token;

  public function __construct()
  {
    $this->baseUrl = "https://" . config('shopify.store') . ".myshopify.com/admin/api/" . config('shopify.version') . "/graphql.json";
    $this->token   = config('shopify.token');
  }


  /**
   * Obtener lista de productos desde Shopify
   */
  /**
   * Obtener productos (index o search)
   *
   * @param int $limit
   * @param string|null $searchTerm
   * @param string|null $cursor
   * @return array
   */
  public function getProducts(int $limit = 10, ?string $searchTerm = null, ?string $cursor = null): array
  {
    $query = <<<'GRAPHQL'
        query getProducts($limit: Int!, $query: String, $cursor: String) {
          products(first: $limit, after: $cursor, sortKey: CREATED_AT, reverse: true, query: $query) {
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

    $response = Http::withHeaders([
      'X-Shopify-Access-Token' => $this->token,
      'Content-Type'           => 'application/json',
    ])->post($this->baseUrl, [
      'query' => $query,
      'variables' => [
        'limit'  => $limit,
        'query'  => $searchTerm,
        'cursor' => $cursor
      ]
    ]);

    $edges = $response->json('data.products.edges') ?? [];

    $products = collect($edges)->map(function ($edge) {
      $product = $edge['node'];

      $product['variants'] = collect($product['variants']['edges'])
        ->map(fn($vEdge) => $vEdge['node'])
        ->toArray();

      $product['images'] = collect($product['images']['edges'])
        ->map(fn($iEdge) => $iEdge['node'])
        ->toArray();

      $product['quantity_total'] = collect($product['variants'])->sum('inventoryQuantity');

      $product['cursor'] = $edge['cursor'];

      return $product;
    })->toArray();

    $pageInfo = $response->json('data.products.pageInfo');

    return [
      'products'   => $products,
      'pageInfo'   => $pageInfo,
      'lastCursor' => $products ? end($products)['cursor'] : null,
    ];
  }

  public function getOrders($limit = 10, $cursor = null)
  {

    // Construimos la parte dinámica para paginación
    $afterClause = $cursor ? ", after: \"{$cursor}\"" : "";

    $query = "
              {
                orders(first: {$limit}, sortKey: CREATED_AT, reverse: true{$afterClause}) {
                  edges {
                    cursor
                    node {
                      id
                      name
                      createdAt
                      displayFinancialStatus
                      displayFulfillmentStatus
                      totalPriceSet {
                        shopMoney {
                          amount
                          currencyCode
                        }
                      }
                      customer {
                        firstName
                        lastName
                        email
                      }
                      lineItems(first: 50) {
                        edges {
                          node {
                            id
                            name
                            quantity
                            sku
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
                              image {
                                url
                                altText
                              }
                              product {
                                title
                                featuredImage {
                                  url
                                  altText
                                }
                              }
                            }
                          }
                        }
                      }
                    }
                  }
                  pageInfo {
                    hasNextPage
                    endCursor
                  }
                }
              }";

    $response = Http::withHeaders([
      'X-Shopify-Access-Token' => $this->token,
      'Content-Type' => 'application/json',
    ])->post($this->baseUrl, [
      'query' => $query
    ]);

    if ($response->failed()) {
      return ['error' => 'No se pudieron obtener las órdenes'];
    }

    $result = GraphQLResponseHelper::normalizeEntity(
      $response,
      'orders',  // ← Cambias 'products' por 'orders'
      ['lineItems', 'fulfillments', 'customer']  // ← Los campos anidados de órdenes
    );

    return [
      'orders'   => $result['items'],
      'pageInfo'   => $result['pageInfo'],
      'lastCursor' => $result['lastCursor'],
    ];

    return $orders;
  }
}
