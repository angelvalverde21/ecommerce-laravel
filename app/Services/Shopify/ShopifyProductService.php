<?php

namespace App\Services\Shopify;

use App\Helpers\GraphQLResponseHelper;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\ShopifyProduct;
use App\Models\ShopifyVariant;
use App\Models\Size;
use App\Models\Status;
use App\Models\Store;
use App\Services\Dashboard\Option\OptionService;
use App\Services\Dashboard\OptionValue\OptionValueService;
use App\Services\Shopify\ShopifyBaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\TryCatch;

class ShopifyProductService extends ShopifyBaseService
{

    protected OptionValueService $optionValueService;
    protected OptionService $optionService;

    public function __construct(
        OptionValueService $optionValueService,
        OptionService $optionService
    ) {
        parent::__construct(); // ⚠️ importante si el padre tiene constructor

        $this->optionValueService = $optionValueService;
        $this->optionService = $optionService;
    }

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

    public function sync_(Store $store): array
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

        $this->syncProductsShopify($products->toArray(), $store);
        $this->syncProductsErp($products->toArray(), $store);

        return [
            'data' => json_decode(json_encode($products)),
            // 'pageInfo'   => $result['pageInfo'] ?? null,
            // 'lastCursor' => $result['lastCursor'] ?? null,
        ];
        // Convertir fechas a local

        // Agrupar por fecha

        // Construir el periodo completo
    }

    public function sync(Store $store, bool $forceRefresh = false): array
    {
        // -------------------------------------------------------------
        // 1️⃣ — QUERY GRAPHQL
        // -------------------------------------------------------------
        $queryTemplate = <<<GRAPHQL
                {
                    products(
                        first: 100,
                        after: :cursor,
                        sortKey: CREATED_AT,
                    ) {
                        edges {
                            cursor
                            node {
                                id
                                title
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
                            hasPreviousPage
                            endCursor
                        }
                    }
                }
                GRAPHQL;

        $queryBuilder = function (?string $cursor) use ($queryTemplate) {
            return str_replace(
                [':cursor'],
                [$cursor ? "\"$cursor\"" : 'null'],
                $queryTemplate
            );
        };

        // -------------------------------------------------------------
        // 2️⃣ — SHOPIFY (SIN CACHE)
        // -------------------------------------------------------------
        $result = $this->getDataFromShopify(
            'products',
            $queryBuilder,
            ['variants']
        );

        $products = collect($result['items'] ?? []);

        if ($products->isEmpty()) {
            Log::warning("Shopify sync: productos vacíos (store {$store->id})");
        }

        // -------------------------------------------------------------
        // 3️⃣ — SYNC A ERP / DB
        // -------------------------------------------------------------
        $this->syncProductsShopify($products->toArray(), $store); //Actualiza la tabla de productos de shopify
        $this->syncProductsErp($products->toArray(), $store); //Actualiza la tabla de productos de nuestro ERP, con la info del producto de shopify

        return [
            'data' => json_decode(json_encode($products)),
            'cached' => false,
        ];
    }


    public function syncProductsShopify(array $products, Store $store)
    {
        collect($products)->chunk(10)->each(function ($chunk) use ($store) {

            DB::transaction(function () use ($chunk) {

                foreach ($chunk as $product_shopify) {

                    // ----------------------------------------------
                    // 1) PRODUCTO SHOPIFY
                    // ----------------------------------------------
                    $productModel = ShopifyProduct::updateOrCreate(
                        [
                            'shopify_product_id' => $product_shopify['id'],
                        ],
                        [
                            'title' => $product_shopify['title'],
                            'image' => $product_shopify['featuredImage']['src'] ?? null,
                            'status' => $product_shopify['status'] ?? null,
                            'online_store_url' => $product_shopify['onlineStoreUrl'] ?? null,
                            'created_at_shopify' => Carbon::parse($product_shopify['createdAt']),
                            'updated_at_shopify' => Carbon::parse($product_shopify['updatedAt']),
                        ]
                    );

                    // ----------------------------------------------
                    // 2) VARIANTES
                    // ----------------------------------------------
                    foreach ($product_shopify['variants'] as $v) {

                        ShopifyVariant::updateOrCreate(
                            [
                                'shopify_variant_id' => $v['id'],
                            ],
                            [
                                'shopify_product_id' => $productModel->id,
                                'title' => $v['title'],
                                'sku' => $v['sku'] ?? null,
                                'price_etiqueta' => (float) $v['compareAtPrice'],
                                'price_oferta' => (float) $v['price'],
                                'quantity' => (int) ($v['quantity'] ?? 0),
                            ]
                        );
                    }
                }
            });
        });

        return [
            'synced' => count($products),
            'status' => 'OK'
        ];
    }

    public function syncProductsErp(array $products, Store $store)
    {
        collect($products)->chunk(10)->each(function ($chunk) use ($store) {

            DB::transaction(function () use ($chunk, $store) {

                foreach ($chunk as $product_shopify) {

                    // ----------------------------------------------
                    // 1) CATEGORÍA
                    // ----------------------------------------------
                    $category = Category::updateOrCreate(
                        [
                            'origin' => $product_shopify['category']['id'] ?? null,
                            'store_id' => $store->id,
                        ],
                        [
                            'name' => $product_shopify['category']['name'] ?? 'Predeterminado',
                            'full_name' => $product_shopify['category']['fullName'] ?? 'Predeterminado',
                            'status' => 1,
                            'is_color' => 0,
                            'is_size' => 1,
                            'slug' => Str::slug($product_shopify['category']['name'] ?? 'predeterminado'),
                            'user_id' => Auth::id(),
                        ]
                    );

                    // ----------------------------------------------
                    // 2) PRODUCTO
                    // ----------------------------------------------
                    // $product = Product::updateOrCreate(
                    //     [
                    //         'origin' => $product_shopify['id'],
                    //         'store_id' => $store->id,
                    //     ],
                    //     [
                    //         'name' => $product_shopify['title'],
                    //         'status' => $this->mapShopifyStatus($product_shopify['status'] ?? null),
                    //         'slug' => Str::slug($product_shopify['title']) . '-' . $store->id,
                    //         'online_store_url' => $product_shopify['onlineStoreUrl'] ?? null,
                    //         'user_id' => Auth::id(),
                    //         'category_id' => $category->id,
                    //         'created_at' => Carbon::parse($product_shopify['createdAt']),
                    //         'updated_at' => Carbon::parse($product_shopify['updatedAt']),
                    //     ]
                    // );
                    $product = Product::updateOrCreate(
                        [
                            'origin' => $product_shopify['id'],
                            'store_id' => $store->id,
                        ],
                        [
                            'name' => $product_shopify['title'],
                            'status' => $this->mapShopifyStatus($product_shopify['status'] ?? null),
                            'slug' => Str::slug($product_shopify['title']) . '-' . $store->id . '-' . $product_shopify['id'],
                            'online_store_url' => $product_shopify['onlineStoreUrl'] ?? null,
                            'user_id' => Auth::id(),
                            'category_id' => $category->id,
                            'created_at' => Carbon::parse($product_shopify['createdAt']),
                            'updated_at' => Carbon::parse($product_shopify['updatedAt']),
                        ]
                    );

                    // ----------------------------------------------
                    // 3) IMAGEN (una sola, evitar duplicados)
                    // ----------------------------------------------
                    $src = $product_shopify['featuredImage']['src'] ?? null;

                    if ($src && !$product->images()->exists()) {
                        $product->images()->create([
                            'title' => $product_shopify['title'],
                            'name' => $src,
                            'thumbnail' => shopify_image_by_height($src, 300),
                            'medium'    => shopify_image_by_height($src, 800),
                            'large'     => shopify_image_by_height($src, 1200),
                        ]);
                    }

                    // ----------------------------------------------
                    // 4) OPCIÓN SIZE (una vez)
                    // ----------------------------------------------

                    //Como este shopify solo tiene size, creamos manualmente esa opcion
                    $option = $this->optionService->store($store, $product->id, 'size');

                    // ----------------------------------------------
                    // 5) VARIANTES
                    // ----------------------------------------------
                    foreach ($product_shopify['variants'] as $variant_shopify) {

                        Log::info("Sincronizando opción para variante");
                        Log::info($variant_shopify);

                        $this->optionValueService->store(
                            $store,
                            $option->id,
                            $variant_shopify['title']
                        );
                    }
                }
            });
        });

        return [
            'synced' => count($products),
            'status' => 'OK'
        ];
    }


    private function mapShopifyStatus(?string $status): int
    {
        return match ($status) {
            'ACTIVE'   => Status::ACTIVE,
            'DRAFT'    => Status::DRAFT,
            'ARCHIVED' => Status::ARCHIVED,
            default    => Status::DRAFT, // fallback seguro
        };
    }

    public function getProducts(
        string $status = "ACTIVE",
        int $perPage = 20,
        int $page = 1
    ) {
        $page = max(1, $page);
        return ShopifyProduct::where('status', $status)->orderBy('id', 'desc')
            ->with('variants')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }


    public function getSearchProducts($search = "", $limit = 100)
    {

        //consulta a nuestra base de datos
        return ShopifyProduct::with('variants')->orderBy('id', 'desc')
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
            ->chunk(50, function ($products) use ($query, $type, &$counter) {

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
                            "id" => $variant->shopify_variant_id,
                            "price" => $price,
                            "compareAtPrice" => $variant->price_etiqueta,
                        ];
                    }

                    // Si no hay variantes válidas, saltar
                    if (empty($variants)) {
                        continue;
                    }

                    $variables = [
                        "productId" => $product->shopify_product_id,
                        "variants" => $variants  // aquí pueden venir N variantes
                    ];

                    if ($product->sync_status) {
                        # code...
                        $response = $this->graphql($query, $variables)->json();
                        Log::info($response);
                    }


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

    // public function syncPricesVariants($type, $variants)
    // {

    //     $query = <<<'GRAPHQL'
    //                     mutation updateVariantPrice(
    //                         $productId: ID!, 
    //                         $variants: [ProductVariantsBulkInput!]!
    //                     ) {
    //                         productVariantsBulkUpdate(productId: $productId, variants: $variants) {
    //                             productVariants {
    //                                 id
    //                                 price
    //                                 compareAtPrice
    //                             }
    //                             userErrors {
    //                                 field
    //                                 message
    //                             }
    //                         }
    //                     }
    //                 GRAPHQL;

    //     $counter = 0;

    //     // $variants = [];

    //     if (empty($variants)) {
    //         return;
    //     }

    //     foreach ($variants as $variant) {

    //         // $data["product_id"] = GID del producto
    //         // $data["variants"] = array con N variantes

    //         $price = $variant->{$type} ?? null;

    //         if ($price === null) {
    //             continue; // saltar si no hay precio
    //         }

    //         $variants[] = [
    //             "id" => $variant->shopify_variant_id,
    //             "price" => $price,
    //             "compareAtPrice" => $variant->price_etiqueta,
    //         ];
    //     }

    //     // Si no hay variantes válidas, saltar

    //     $variables = [
    //         "productId" => $product->shopify_product_id,
    //         "variants" => $variants  // aquí pueden venir N variantes
    //     ];

    //     if ($product->sync_status) {
    //         # code...
    //         $response = $this->graphql($query, $variables)->json();
    //         Log::info($response);
    //     }


    //     $counter++;

    //     // pausa cada 5 productos
    //     if ($counter % 5 === 0) {
    //         usleep(200_000); // 200ms
    //     }

    // }
}
