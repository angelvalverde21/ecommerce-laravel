<?php

use App\Http\Controllers\Api\Shopify\PdfShopifyController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/cache', function () {

    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');

    return "Cacheado!x";
});
Route::get('/optimize', function () {

    Artisan::call('optimize:clear');

    return "optimize";
});
Route::get('/config', function () {

    Artisan::call('config:clear');
    Artisan::call('config:cache');

    // Read the regenerated cache: this request may still hold the previous configuration.
    $cachePath = app()->getCachedConfigPath();
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($cachePath, true);
    }
    $cachedConfig = require $cachePath;
    $shopify = $cachedConfig['shopify'] ?? [];
    $environmentFile = app()->environmentFile();
    $environment = \Illuminate\Support\Env::get('APP_ENV');
    if ($environment && is_file(app()->environmentPath().DIRECTORY_SEPARATOR.$environmentFile.'.'.$environment)) {
        $environmentFile .= '.'.$environment;
    }
    $environmentPath = app()->environmentPath().DIRECTORY_SEPARATOR.$environmentFile;
    $fileValues = [];
    $fileStatus = 'not_readable';
    if (is_readable($environmentPath)) {
        try {
            $fileValues = \Dotenv\Dotenv::parse(file_get_contents($environmentPath));
            $fileStatus = 'readable';
        } catch (\Throwable) {
            $fileStatus = 'invalid_format';
        }
    }
    // Only report this non-secret setting; never return the environment file or access tokens.
    $serverSlug = getenv('SHOPIFY_CATALOG_STORE_SLUG');

    return response()->json([
        'message' => 'Configuración actualizada',
        'catalog_setting_present' => array_key_exists('catalog_store_slug', $shopify),
        'catalog_store_slug' => $shopify['catalog_store_slug'] ?? null,
        'environment_file' => basename($environmentFile),
        'environment_file_status' => $fileStatus,
        'file_catalog_setting_present' => array_key_exists('SHOPIFY_CATALOG_STORE_SLUG', $fileValues),
        'file_catalog_store_slug' => $fileValues['SHOPIFY_CATALOG_STORE_SLUG'] ?? null,
        'server_catalog_store_slug' => $serverSlug === false ? null : $serverSlug,
    ])->header('Cache-Control', 'no-store');
});

Route::get('/link', function () {
    // Artisan::call('storage:link');
    // Artisan::call('storage:link');
    File::link(
        storage_path('app/public'), public_path('storage')
    );
 });

 Route::get('/clear', function () {
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('config:cache');
    return 'cleared';
});

Route::get('/pdf', [PdfShopifyController::class, 'test']); //Listar
