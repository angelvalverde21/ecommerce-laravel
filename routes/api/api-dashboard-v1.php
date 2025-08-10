<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
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


});


