<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\BatcheDashboardController;
use App\Http\Controllers\Api\Dashboard\IdentityDashboardController;
use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Http\Controllers\Api\Dashboard\PurchaseDashboardController;
use App\Http\Controllers\Api\Dashboard\SupplierDashboardController;
use App\Http\Controllers\Api\Dashboard\UnitDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/dashboard/{store}')->group(function () {

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
    
        });
    
    });


    Route::prefix('batches')->group(function () {
    
        Route::get('/', [BatcheDashboardController::class, 'index']); //Listar
        Route::post('/', [BatcheDashboardController::class, 'store']); //create
    
        Route::prefix('{brand_id}')->group(function () {
    
            Route::get('/', [BatcheDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [BatcheDashboardController::class, 'update']); //actualizar
            Route::delete('/', [BatcheDashboardController::class, 'destroy']); //borrar
    
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


});


