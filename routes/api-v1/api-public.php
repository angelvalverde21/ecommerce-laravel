<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\public\AndroidController;
use App\Http\Controllers\Api\public\StorePublicController;
use App\Http\Controllers\Api\public\WebPublicController;
use App\Http\Controllers\Api\public\YapePublicController;
use App\Http\Controllers\Api\Shopify\App\AppShopifyController;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyStore;


// Route::get('/cache', function () {

//     Artisan::call('cache:clear');
//     Artisan::call('config:cache');
//     Artisan::call('route:cache');
//     Artisan::call('view:cache');

//     return "Cacheado!x";
// });

// Route::get('/link', function () {
//     Artisan::call('storage:link');
// });

Route::prefix('v1/public')->middleware('api')->group(function () {

    Route::post('/register', [StorePublicController::class, 'register']);

    
    Route::prefix('{store}')->middleware([VerifyStore::class])->group(function () {
        
        Route::post('/yape', [YapePublicController::class, 'store']);
        Route::get('/', [StorePublicController::class, 'show']);
        Route::get('/price/{variant_id}', [AndroidController::class, 'price']);
        Route::post('/login', [AuthApiController::class, 'login']);

        //Todoko

        Route::get('/tracking/{order_id}', [WebPublicController::class, 'tracking']);

        Route::prefix('app-shopify')->group(function () {

            Route::post('/tracking', [AppShopifyController::class, 'tracking']); //create

        });
    });


});

//https://3b.pe/laravel/api/v1/public/sorelle