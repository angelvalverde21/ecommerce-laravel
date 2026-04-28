<?php

use App\Http\Controllers\Api\Pasarelas\MercadoPagoController;
use App\Http\Controllers\Api\Shopify\OrderShopifyController;
use App\Http\Controllers\Api\Shopify\PdfShopifyController;
use App\Http\Controllers\Api\Shopify\ProductShopifyController;
use App\Http\Controllers\Api\Shopify\ReportShopifyController;
use App\Http\Controllers\Api\Shopify\SyncShopifyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/{store}/dashboard/shopify')->middleware('api')->middleware(['auth:api'])->group(function () {

    Route::prefix('orders')->group(function () {

        Route::get('/', [OrderShopifyController::class, 'index']); //Listar
        Route::get('/pending', [OrderShopifyController::class, 'pending']); //Listar
        Route::get('/search/{search?}', [OrderShopifyController::class, 'search']); //Listar
        Route::get('/prepared', [OrderShopifyController::class, 'prepared']); //Listar

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
        Route::get('/draft', [ProductShopifyController::class, 'draft']); //Listar
        Route::get('/active', [ProductShopifyController::class, 'active']); //Listar
        Route::get('/archived', [ProductShopifyController::class, 'archived']); //Listar

        // Route::get('/search/{search?}', [ProductShopifyController::class, 'search']); //Listar

        Route::post('/search', [ProductShopifyController::class, 'search']); //Listar

        Route::prefix('sync')->group(function () {

            Route::get('/', [ProductShopifyController::class, 'sync']); //Sincroniza los productos de shopify
            // Route::put('/', [ProductShopifyController::class, 'syncPrices']); //actualizar
            Route::put('/prices', [SyncShopifyController::class, 'syncPrices']); //actualizar
            Route::put('/price', [SyncShopifyController::class, 'syncPrice']); //actualizar

        });

        // Route::put('/prices/massive', [ProductShopifyController::class, 'updatePriceMassive']); //actualizar
        Route::put('/prices', [ProductShopifyController::class, 'updatePrices']); //actualizar
        Route::put('/price', [ProductShopifyController::class, 'updatePrice']); //actualizar

        Route::post('/', [ProductShopifyController::class, 'store']); //create

        Route::prefix('{product_id}')->group(function () {

            Route::get('/', [ProductShopifyController::class, 'show']); //show o mostrar por id
            Route::put('/sync-status', [ProductShopifyController::class, 'updateProductSyncStatus']); //actualizar
            Route::put('/', [ProductShopifyController::class, 'update']); //actualizar
            Route::put('/prices', [ProductShopifyController::class, 'updateProductVariantPrices']); //actualizar las variantes de un producto
            //  Route::put('/sync/prices', [SyncShopifyController::class, 'syncProductPrices']); //actualizar (no tiene sentido)
            Route::delete('/', [ProductShopifyController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('reports')->group(function () {

        Route::get('/', [ReportShopifyController::class, 'index']); //Listar
        Route::get('/top', [ReportShopifyController::class, 'reportTopSellingProducts']); //Listar

        Route::prefix('bars')->group(function () {
            Route::get('/daily/{days?}', [ReportShopifyController::class, 'reportBarDailys']); //Listar
            Route::get('/months/{days?}', [ReportShopifyController::class, 'reportBarMonths']); //Listar
        });

        Route::get('/month-all', [ReportShopifyController::class, 'monthAll']); //Listar
        Route::get('/report-cash-weekly', [ReportShopifyController::class, 'reportCashWeekly']); //Listar


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
