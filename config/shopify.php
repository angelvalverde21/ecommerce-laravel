<?php

return [
    'store'   => env('SHOPIFY_STORE', ''),
    'version' => env('SHOPIFY_API_VERSION', ''),
    'token'   => env('SHOPIFY_ACCESS_TOKEN', ''),
    'order_import_enabled' => env('SHOPIFY_ORDER_IMPORT_ENABLED', false),
    'order_import_store_slug' => env('SHOPIFY_ORDER_IMPORT_STORE_SLUG', ''),
    'initial_import_store_slug' => env('SHOPIFY_INITIAL_IMPORT_STORE_SLUG', env('SHOPIFY_ORDER_IMPORT_STORE_SLUG', '')),
    'initial_import_version' => env('SHOPIFY_INITIAL_IMPORT_API_VERSION', '2026-10'),
];
