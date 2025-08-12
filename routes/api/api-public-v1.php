<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\public\StorePublicController;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;




Route::prefix('v1/public/{store}')->group(function () {

    Route::get('/', [StorePublicController::class, 'show']);

    Route::post('/login', [AuthApiController::class, 'login']);

    Route::get('/cache', function () {

        Artisan::call('cache:clear');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return "Cacheado!x";
    });

    Route::get('/link', function () {
        Artisan::call('storage:link');
    });
});
