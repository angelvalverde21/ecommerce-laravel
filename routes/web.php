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

    return "config";
});

Route::get('/link', function () {
    Artisan::call('storage:link');
    // Artisan::call('storage:link');
    // File::link(
    //     storage_path('app/public'), public_path('storage')
    // );
 });

 Route::get('/clear', function () {
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('config:cache');
    return 'cleared';
});

Route::get('/pdf', [PdfShopifyController::class, 'test']); //Listar