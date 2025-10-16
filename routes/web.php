<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/cache', function () {

    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');

    return "Cacheado!x";
});

Route::get('/link', function () {
    $exitCode = Artisan::call('storage:link');
    return [
        'public_path' => public_path('storage'),
        'storage_path' => storage_path('app/public'),
        'output' => Artisan::output(),
    ];
});