<?php

use App\Http\Controllers\Api\Pasarelas\MercadoPagoController;
use App\Http\Controllers\Api\Shopify\OrderShopifyController;
use App\Http\Controllers\Api\Shopify\PdfShopifyController;
use App\Http\Controllers\Api\Shopify\ProductShopifyController;
use App\Http\Controllers\Api\Shopify\ReportShopifyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/{store}/dashboard/shopify')->middleware('api')->group(function () {

    Route::prefix('orders')->group(function () {

        Route::get('/', [OrderShopifyController::class, 'index']); //Listar

        Route::post('/', [OrderShopifyController::class, 'store']); //create

        Route::prefix('{order_id}')->group(function () {

            Route::get('/', [OrderShopifyController::class, 'show']); //show o mostrar por id
            Route::put('/', [OrderShopifyController::class, 'update']); //actualizar
            Route::delete('/', [OrderShopifyController::class, 'destroy']); //borrar

            Route::prefix('pdf')->group(function () {

                Route::get('/voucher', [PdfShopifyController::class, 'voucher']); //Listar
                Route::get('/', [PdfShopifyController::class, 'index']); //Listar
                Route::post('/', [PdfShopifyController::class, 'store']); //create

            });
        });
    });

    Route::prefix('products')->group(function () {

        Route::get('/', [ProductShopifyController::class, 'index']); //Listar
        Route::get('/search/{search?}', [ProductShopifyController::class, 'search']); //Listar
        Route::post('/', [ProductShopifyController::class, 'store']); //create

        Route::prefix('{product_id}')->group(function () {

            Route::get('/', [ProductShopifyController::class, 'show']); //show o mostrar por id
            Route::put('/', [ProductShopifyController::class, 'update']); //actualizar
            Route::delete('/', [ProductShopifyController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('reports')->group(function () {

        Route::get('/', [ReportShopifyController::class, 'index']); //Listar
        Route::get('/top', [ReportShopifyController::class, 'topProducts']); //Listar

        Route::prefix('bars')->group(function () {
            Route::get('/daily/{days?}', [ReportShopifyController::class, 'dailyOrders']); //Listar
        });

        Route::get('/month-all', [ReportShopifyController::class, 'monthAll']); //Listar


    });

    Route::prefix('pasarelas/mercadopago')->group(function () {

        Route::post('/create-link', [MercadoPagoController::class, 'createLink']); //Listar
        Route::get('/transactions', [MercadoPagoController::class, 'transactions']); //Listar

        Route::prefix('{transaction_id}')->group(function () {

            Route::get('/', [ProductShopifyController::class, 'show']); //show o mostrar por id
            Route::put('/', [ProductShopifyController::class, 'update']); //actualizar
            Route::delete('/', [ProductShopifyController::class, 'destroy']); //borrar

        });
    });
});
