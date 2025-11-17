<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/cache', function () {

    Artisan::call('config:cache');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');

    return "Cacheado!x";
});
Route::get('/optimize', function () {

    Artisan::call('optimize:clear');

    return "Cacheado!x";
});

Route::get('/link', function () {
    // Artisan::call('storage:link');
    // Artisan::call('storage:link');
    File::link(
        storage_path('app/public'), public_path('storage')
    );
 });