<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\AttributeDashboardController;
use App\Http\Controllers\Api\Dashboard\CategoryDashboardController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Dashboard\IdentityDashboardController;
use App\Http\Controllers\Api\Dashboard\Images\ImageDashboardController;
use App\Http\Controllers\Api\Dashboard\Images\ImagePurchaseController;
use App\Http\Controllers\Api\Dashboard\Images\ImageProductController;
use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Http\Controllers\Api\Dashboard\PurchaseDashboardController;
use App\Http\Controllers\Api\Dashboard\SizeDashboardController;
use App\Http\Controllers\Api\Dashboard\SupplierDashboardController;
use App\Http\Controllers\Api\Dashboard\UnitDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/dashboard/{store}')->middleware(['auth:api'])->group(function () {

    Route::get('/', [DashboardController::class, 'getDashboard']); //Listar

    Route::prefix('products')->group(function () {

        Route::get('/', [ProductDashboardController::class, 'index']); //Listar
        Route::post('/', [ProductDashboardController::class, 'store']); //create

        Route::prefix('{product_id}')->group(function () {

            Route::get('/', [ProductDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [ProductDashboardController::class, 'update']); //actualizar
            Route::delete('/', [ProductDashboardController::class, 'destroy']); //borrar

            Route::prefix('images')->group(function () {
                Route::post('/', [ImageProductController::class, 'store']); //create
                Route::get('/', [ImageProductController::class, 'index']); //show
            });

            Route::prefix('sizes')->group(function () {
            
                Route::get('/', [SizeDashboardController::class, 'index']); //Listar
                Route::post('/', [SizeDashboardController::class, 'store']); //create
                Route::put('/sort', [SizeDashboardController::class, 'sort']); //actualizar
            
                Route::prefix('{size_id}')->group(function () {
            
                    Route::get('/', [SizeDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [SizeDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [SizeDashboardController::class, 'destroy']); //borrar
            
                });
            
            });

            Route::prefix('attributes')->group(function () {
            
                Route::get('/', [AttributeDashboardController::class, 'index']); //Listar
                Route::post('/', [AttributeDashboardController::class, 'store']); //create
            
                Route::prefix('{attribute_id}')->group(function () {
            
                    Route::get('/', [AttributeDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [AttributeDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [AttributeDashboardController::class, 'destroy']); //borrar
            
                });
            
            });

        });
    });

    Route::prefix('categories')->group(function () {
    
        Route::get('/', [CategoryDashboardController::class, 'index']); //Listar
        Route::post('/', [categoryDashboardController::class, 'store']); //create
        
        Route::prefix('{category_id}')->group(function () {
    
            Route::get('/', [categoryDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [categoryDashboardController::class, 'update']); //actualizar
            Route::delete('/', [categoryDashboardController::class, 'destroy']); //borrar
    
        });

         Route::get('/slug/{slug}', [CategoryDashboardController::class, 'showListBySlug']);
    
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
        Route::get('/search/{search}', [SupplierDashboardController::class, 'search']); //Listar

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

    Route::prefix('identities')->group(function () {

        Route::get('/', [IdentityDashboardController::class, 'index']); //Listar
        Route::post('/', [IdentityDashboardController::class, 'store']); //create

        Route::prefix('{identity_id}')->group(function () {

            Route::get('/', [IdentityDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [IdentityDashboardController::class, 'update']); //actualizar
            Route::delete('/', [IdentityDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('images')->group(function () {
        Route::delete('/{image_id}', [ImageDashboardController::class, 'destroy']); //eliminar registro
    });
});
