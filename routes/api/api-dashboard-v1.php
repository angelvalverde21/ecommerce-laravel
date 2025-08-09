<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
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


});


