<?php

// use App\Http\Controllers\api\dashboard\ProductDashboardController;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');


// Route::middleware('auth:api')->prefix('v1')->group(function () {
//     Route::get('/', [ProductDashboardController::class, 'index']);
// });

require __DIR__ . '/api/api-public-v1.php';
require __DIR__ . '/api/api-dashboard-v1.php';