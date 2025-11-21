<?php

use App\Http\Controllers\api\dashboard\ProductDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/', function (Request $request) {
//     return "Hola Api";
// });


// Route::middleware('auth:api')->prefix('v1')->group(function () {
//     Route::get('/', [ProductDashboardController::class, 'index']);
// });

require __DIR__ . '/api-v1/api-dashboard.php';
require __DIR__ . '/api-v1/api-shopify.php';
require __DIR__ . '/api-v1/api-public.php';
require __DIR__ . '/api-v1/api-stores.php';
require __DIR__ . '/api-v1/api-gateways.php';