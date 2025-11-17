<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:4200',
        'http://localhost:4300',
        'http://3b.pe',
        'https://3b.pe',
        'https://api.3b.pe',
        'http://www.3b.pe',
        'https://www.3b.pe',
        'http://luvadi.pe',
        'https://luvadi.pe',
        'http://www.luvadi.pe',
        'https://www.luvadi.pe',
        'https://www.luvadi.store',
        'https://luvadi.store',
        'http://www.luvadi.store',
        'http://luvadi.store',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
