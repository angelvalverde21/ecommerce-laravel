<?php

return [
    'store'   => env('SHOPIFY_STORE', ''),
    'version' => env('SHOPIFY_API_VERSION', ''),
    'token'   => env('SHOPIFY_ACCESS_TOKEN', ''),
    'order_import_enabled' => env('SHOPIFY_ORDER_IMPORT_ENABLED', false),
    'order_import_store_slug' => env('SHOPIFY_ORDER_IMPORT_STORE_SLUG', ''),
];
