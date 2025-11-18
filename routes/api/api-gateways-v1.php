<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use Illuminate\Support\Facades\Route;

// Route::prefix('v1/dashboard/{store}')->middleware(['auth:api'])->group(function () {

// });

// Route::prefix('v1/dashboard/{store}/gateways')->middleware(['auth:api'])->group(function () {

//     Route::prefix('mercadopago')->group(function () {
    
//         Route::prefix('payments')->group(function () {
        
//             Route::get('/', [PaymentMercadoPagoController::class, 'index']); //Listar
//             Route::post('/', [PaymentMercadoPagoController::class, 'store']); //create
        
//             Route::prefix('{payment_id}')->group(function () {
        
//                 Route::get('/', [PaymentMercadoPagoController::class, 'show']); //show o mostrar por id
//                 Route::put('/', [PaymentMercadoPagoController::class, 'update']); //actualizar
//                 Route::delete('/', [PaymentMercadoPagoController::class, 'destroy']); //borrar
        
//             });
        
//         });
    
//     });

// });

