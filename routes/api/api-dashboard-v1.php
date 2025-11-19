<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\BrandDashboardController;
use App\Http\Controllers\Api\Dashboard\CategoryDashboardController;
use App\Http\Controllers\Api\Dashboard\ColorDashboardController;
use App\Http\Controllers\Api\Dashboard\Images\ImageDashboardController;
use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Http\Controllers\Api\Dashboard\SizeDashboardController;
use App\Http\Controllers\Api\Dashboard\StoreDashboardController;
use Illuminate\Support\Facades\Route;

// Route::prefix('v1/dashboard/{store}')->middleware(['auth:api'])->group(function () {

// });

Route::prefix('v1/dashboard/{store}')->middleware('api')->group(function () {

    Route::get('/', [StoreDashboardController::class, 'show']); //Listar

    Route::prefix('categories')->group(function () {
    
        Route::get('/', [CategoryDashboardController::class, 'index']); //Listar
        Route::post('/', [CategoryDashboardController::class, 'store']); //create
    
        Route::prefix('{category_id}')->group(function () {
    
            Route::get('/', [CategoryDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [CategoryDashboardController::class, 'update']); //actualizar
            Route::delete('/', [CategoryDashboardController::class, 'destroy']); //borrar
    
        });
    
    });

    Route::prefix('products')->group(function () {

        Route::get('/setup', [ProductDashboardController::class, 'setup']); //Listar
        Route::get('/', [ProductDashboardController::class, 'index']); //Listar
        Route::get('/search/{search}', [ProductDashboardController::class, 'search']); //Listar
        Route::post('/', [ProductDashboardController::class, 'store']); //create

        Route::prefix('{product_id}')->group(function () {

            Route::get('/', [ProductDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [ProductDashboardController::class, 'update']); //actualizar
            Route::delete('/', [ProductDashboardController::class, 'destroy']); //borrar

            Route::prefix('images')->group(function () {
            
                Route::get('/', [ImageDashboardController::class, 'index']); //Listar
                Route::post('/', [ImageDashboardController::class, 'store']); //create
            
                Route::prefix('{brand_id}')->group(function () {
            
                    Route::get('/', [ImageDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [ImageDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [ImageDashboardController::class, 'destroy']); //borrar
            
                });
            
            });


            Route::prefix('sizes')->group(function () {
            
                Route::get('/', [SizeDashboardController::class, 'index']); //Listar
                Route::put('/sort', [SizeDashboardController::class, 'sort']); //actualizar
                Route::post('/', [SizeDashboardController::class, 'store']); //create
            
                Route::prefix('{size_id}')->group(function () {
            
                    Route::get('/', [SizeDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [SizeDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [SizeDashboardController::class, 'destroy']); //borrar
            
                });
            
            });

            Route::prefix('colors')->group(function () {
            
                Route::get('/', [ColorDashboardController::class, 'index']); //Listar
                Route::post('/', [ColorDashboardController::class, 'store']); //create
                Route::put('/sort', [ColorDashboardController::class, 'sort']); //actualizar

                Route::prefix('{color_id}')->group(function () {
            
                    Route::get('/', [ColorDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [ColorDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [ColorDashboardController::class, 'destroy']); //borrar
            
                });
            
            });

        });
    });


    Route::prefix('brands')->group(function () {
    
        Route::get('/', [BrandDashboardController::class, 'index']); //Listar
        Route::post('/', [BrandDashboardController::class, 'store']); //create
    
        Route::prefix('{brand_id}')->group(function () {
    
            Route::get('/', [BrandDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [BrandDashboardController::class, 'update']); //actualizar
            Route::delete('/', [BrandDashboardController::class, 'destroy']); //borrar
    
        });
    
    });
});