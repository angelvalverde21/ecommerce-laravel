<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Models\ShopifyProduct;
use App\Models\ShopifyVariant;
use App\Services\Shopify\ShopifyBaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopifyProductService extends ShopifyBaseService
{

    public function getShopifyProducts(int $limit = 10, ?string $searchTerm = null, ?string $cursor = null): array //son los productos de shopify
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
                                            compareAtPrice
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
                [$cursor ? "\"$cursor\"" : 'null'], //Con estos valores
                $queryTemplate //En el template
            );
        };

        // -------------------------------------------------------------
        // 3️⃣ — Ejecutar el query builder ($queryBuilder) para traer los datos de Shopify
        // -------------------------------------------------------------
        $result = $this->getDataFromShopify(
            'products',
            $queryBuilder,
            ['variants', 'images'] //podria ser 'lineItems'
        );

        // -------------------------------------------------------------
        // 4️⃣ — Normalizar items
        // -------------------------------------------------------------
        $products = collect($result['items'] ?? []); //items es como devuelve getDataFromShopify

        if ($products->isEmpty()) {
            Log::warning('Products: vacío');
        }

        // Log::info($products);

        $products = collect($result['items'] ?? []);

        //================ INICIAMOS LA SCINRONIZACIÓN EN LA BASE DE DATOS LOCAL ====//

        $this->syncProducts($products->toArray());

        return [
            'data' => json_decode(json_encode($products)),
            // 'pageInfo'   => $result['pageInfo'] ?? null,
            // 'lastCursor' => $result['lastCursor'] ?? null,
        ];
        // Convertir fechas a local

        // Agrupar por fecha

        // Construir el periodo completo
    }

    public function syncProducts(array $products)
    {
        foreach ($products as $p) {

            // ----------------------------------------------
            // 1) GUARDAR / ACTUALIZAR PRODUCTO
            // ----------------------------------------------
            $productModel = ShopifyProduct::updateOrCreate(
                [
                    'shopify_product_id' => $p['id'], // GID
                ],
                [
                    'title' => $p['title'],
                    'image' => $p['featuredImage']['src'] ?? null,
                    'status' => $p['status'] ?? null,
                    'online_store_url' => $p['onlineStoreUrl'] ?? null,

                    // Conversión obligatoria
                    'created_at_shopify' => Carbon::parse($p['createdAt'])->toDateTimeString(),
                    'updated_at_shopify' => Carbon::parse($p['updatedAt'])->toDateTimeString(),
                ]
            );

            // ----------------------------------------------
            // 2) GUARDAR VARIANTES
            // ----------------------------------------------
            foreach ($p['variants'] as $v) {

                ShopifyVariant::updateOrCreate(
                    [
                        'shopify_variant_id' => $v['id'], // GID
                    ],
                    [
                        'shopify_product_id' => $productModel->id, // relación LOCAL
                        'title' => $v['title'],
                        'sku' => $v['sku'] ?? null,
                        'price_etiqueta' => $v['compareAtPrice'],
                        'price_oferta' => $v['price'],
                        'quantity' => $v['quantity'] ?? 0,
                    ]
                );
            }
        }

        return [
            'synced' => count($products),
            'status' => 'OK'
        ];
    }

    public function getProducts($status = "ACTIVE")
    {

        return ShopifyProduct::where('status', $status)->with('variants')->get();
    }


    public function getSearchProducts($search = "", $limit = 100)
    {

        //consulta a nuestra base de datos
        return ShopifyProduct::with('variants')
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->limit($limit)
            ->get();
    }

    public function syncPrice($data)
    {
        $query = <<<'GRAPHQL'
        mutation updateVariantPrice(
            $productId: ID!, 
            $variants: [ProductVariantsBulkInput!]!
        ) {
            productVariantsBulkUpdate(productId: $productId, variants: $variants) {
                productVariants {
                    id
                    price
                    compareAtPrice
                }
                userErrors {
                    field
                    message
                }
            }
        }
    GRAPHQL;

        // $data["product_id"] = GID del producto
        // $data["variants"] = array con N variantes

        $variables = [
            "productId" => $data["product_id"],
            "variants" => $data["variants"]  // aquí pueden venir N variantes
        ];

        return $this->graphql($query, $variables)->json();

        /*  Data debe estar en el siguiente formato
            $data = [
                "product_id" => "gid://shopify/Product/7957731573984",
                "variants" => [
                    [
                        "id" => "gid://shopify/ProductVariant/48124316057824",
                        "price" => "119.90",
                        "compareAtPrice" => "149.90"
                    ],
                    [
                        "id" => "gid://shopify/ProductVariant/48124316057825",
                        "price" => "119.90",
                        "compareAtPrice" => "149.90"
                    ],
                    [
                        "id" => "gid://shopify/ProductVariant/48124316057826",
                        "price" => "119.90",
                        "compareAtPrice" => "149.90"
                    ]
                ]
            ];
        */
    }

    public function syncPrices($type)
    {

        $query = <<<'GRAPHQL'
                        mutation updateVariantPrice(
                            $productId: ID!, 
                            $variants: [ProductVariantsBulkInput!]!
                        ) {
                            productVariantsBulkUpdate(productId: $productId, variants: $variants) {
                                productVariants {
                                    id
                                    price
                                    compareAtPrice
                                }
                                userErrors {
                                    field
                                    message
                                }
                            }
                        }
                    GRAPHQL;

        $counter = 0;

        ShopifyProduct::with('variants')
            ->where('status', 'ACTIVE')
            ->chunk(50, function ($products) use ($query, $type, $counter) {

                foreach ($products as $product) {

                    $variants = [];

                    foreach ($product->variants as $variant) {

                        // $data["product_id"] = GID del producto
                        // $data["variants"] = array con N variantes

                        $price = $variant->{$type} ?? null;

                        if ($price === null) {
                            continue; // saltar si no hay precio
                        }

                        $variants[] = [
                            [
                                "id" => $variant->shopify_variant_id,
                                "price" => $price,
                                "compareAtPrice" => $variant->price_etiqueta,
                            ],
                        ];
                    }

                    // ✅ Si no hay variantes válidas, saltar
                    if (empty($variants)) {
                        continue;
                    }

                    $variables = [
                        "productId" => $product->shopify_product_id,
                        "variants" => $variants  // aquí pueden venir N variantes
                    ];

                    $response = $this->graphql($query, $variables)->json();

                    $counter++;

                    // pausa cada 5 productos
                    if ($counter % 5 === 0) {
                        usleep(200_000); // 200ms
                    }
                }
            });


        /*  Data debe estar en el siguiente formato
            $data = [
                "product_id" => "gid://shopify/Product/7957731573984",
                "variants" => [
                    [
                        "id" => "gid://shopify/ProductVariant/48124316057824",
                        "price" => "119.90",
                        "compareAtPrice" => "149.90"
                    ],
                    [
                        "id" => "gid://shopify/ProductVariant/48124316057825",
                        "price" => "119.90",
                        "compareAtPrice" => "149.90"
                    ],
                    [
                        "id" => "gid://shopify/ProductVariant/48124316057826",
                        "price" => "119.90",
                        "compareAtPrice" => "149.90"
                    ]
                ]
            ];
        */
    }
}
