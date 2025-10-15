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


    protected function buildOrderQuery(): string
    {
        return <<<GQL
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
        GQL;
    }

    public function buildOrdersQuery($limit = 10, $cursor = null, $startDate = null, $endDate = null)
    {

        // Fechas por defecto → último mes
        $startDate = $startDate ?? now()->subMonth()->startOfDay()->toDateString();
        $endDate = $endDate ?? now()->endOfDay()->toDateString();

        // Cursor opcional (paginación)
        $afterClause = $cursor ? ', after: "' . $cursor . '"' : '';

        // Filtro de búsqueda Shopify con AND explícitos
        $queryFilter = 'financial_status:paid AND cancelled_at:null AND created_at:>=' . $startDate . ' AND created_at:<=' . $endDate;

        // Query GraphQL como string plano (sin heredoc)
        $query = '
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
                                ' . $this->buildOrderQuery() . '
                            }
                        }
                    }
                }';

        Log::info($query);

        return $query;
    }
}
