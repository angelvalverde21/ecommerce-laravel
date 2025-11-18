<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Http\Controllers\Api\Dashboard\StoreDashboardController;
use Illuminate\Support\Facades\Route;

// Route::prefix('v1/dashboard/{store}')->middleware(['auth:api'])->group(function () {

// });

Route::prefix('v1/stores/{store}')->middleware('api')->group(function () {

    Route::get('/', [StoreDashboardController::class, 'show']); //Listar
    
});

