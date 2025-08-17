<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\BatchDashboardController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Dashboard\IdentityDashboardController;
use App\Http\Controllers\Api\Dashboard\ImageDashboardController;
use App\Http\Controllers\Api\Dashboard\ImagePurchaseController;
use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Http\Controllers\Api\Dashboard\PurchaseDashboardController;
use App\Http\Controllers\Api\Dashboard\SectionDashboardController;
use App\Http\Controllers\Api\Dashboard\SupplierDashboardController;
use App\Http\Controllers\Api\Dashboard\UnitDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/dashboard/{store}')->group(function () {

    Route::get('/', [DashboardController::class, 'getDashboard']); //Listar

    Route::prefix('products')->group(function () {

        Route::get('/', [ProductDashboardController::class, 'index']); //Listar
        Route::post('/', [ProductDashboardController::class, 'store']); //create

        Route::prefix('{product_id}')->group(function () {

            Route::get('/', [ProductDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [ProductDashboardController::class, 'update']); //actualizar
            Route::delete('/', [ProductDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('units')->group(function () {

        Route::get('/', [UnitDashboardController::class, 'index']); //Listar
        Route::post('/', [UnitDashboardController::class, 'store']); //create

        Route::prefix('{unit_id}')->group(function () {

            Route::get('/', [UnitDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [UnitDashboardController::class, 'update']); //actualizar
            Route::delete('/', [UnitDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('suppliers')->group(function () {

        Route::get('/', [SupplierDashboardController::class, 'index']); //Listar
        Route::post('/', [SupplierDashboardController::class, 'store']); //create

        Route::prefix('{supplier_id}')->group(function () {

            Route::get('/', [SupplierDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [SupplierDashboardController::class, 'update']); //actualizar
            Route::delete('/', [SupplierDashboardController::class, 'destroy']); //borrar

        });
    });


    Route::prefix('purchases')->group(function () {

        Route::get('/', [PurchaseDashboardController::class, 'index']); //Listar
        Route::post('/', [PurchaseDashboardController::class, 'store']); //create

        Route::prefix('{purchase_id}')->group(function () {

            Route::get('/', [PurchaseDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [PurchaseDashboardController::class, 'update']); //actualizar
            Route::delete('/', [PurchaseDashboardController::class, 'destroy']); //borrar

            Route::prefix('images')->group(function () {
                Route::post('/', [ImagePurchaseController::class, 'store']); //create
                Route::get('/', [ImagePurchaseController::class, 'index']); //show
            });
        });
    });


    Route::prefix('batches')->group(function () {

        Route::get('/', [BatchDashboardController::class, 'index']); //Listar
        Route::post('/', [BatchDashboardController::class, 'store']); //create

        Route::prefix('{brand_id}')->group(function () {

            Route::get('/', [BatchDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [BatchDashboardController::class, 'update']); //actualizar
            Route::delete('/', [BatchDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('identities')->group(function () {

        Route::get('/', [IdentityDashboardController::class, 'index']); //Listar
        Route::post('/', [IdentityDashboardController::class, 'store']); //create

        Route::prefix('{identity_id}')->group(function () {

            Route::get('/', [IdentityDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [IdentityDashboardController::class, 'update']); //actualizar
            Route::delete('/', [IdentityDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('sections')->group(function () {

        Route::get('/', [SectionDashboardController::class, 'index']); //Listar
        Route::post('/', [SectionDashboardController::class, 'store']); //create

        Route::prefix('{brand_id}')->group(function () {

            Route::get('/', [SectionDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [SectionDashboardController::class, 'update']); //actualizar
            Route::delete('/', [SectionDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('images')->group(function () {
        Route::delete('/{image_id}', [ImageDashboardController::class, 'destroy']); //eliminar registro
    });
});
