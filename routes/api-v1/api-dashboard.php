<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\BrandDashboardController;
use App\Http\Controllers\Api\Dashboard\CategoryDashboardController;
use App\Http\Controllers\Api\Dashboard\ColorDashboardController;
use App\Http\Controllers\Api\Dashboard\CustomerDashboardController;
use App\Http\Controllers\Api\Dashboard\EmployeeDashboardController;
use App\Http\Controllers\Api\Dashboard\Images\ImageDashboardController;
use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Http\Controllers\Api\Dashboard\PurchaseDashboardController;
use App\Http\Controllers\Api\Dashboard\RoleDashboardController;
use App\Http\Controllers\Api\Dashboard\SizeDashboardController;
use App\Http\Controllers\Api\Dashboard\StoreDashboardController;
use App\Http\Controllers\Api\Dashboard\SupplierDashboardController;
use App\Http\Controllers\Api\Dashboard\UserDashboardController;
use Illuminate\Support\Facades\Route;

// Route::prefix('v1/dashboard/{store}')->middleware(['auth:api'])->group(function () {

// });

Route::prefix('v1/{store}/dashboard')->middleware('api')->group(function () {

    Route::get('/', [StoreDashboardController::class, 'show']); //Listar

    Route::prefix('roles')->group(function () {

        Route::get('/', [RoleDashboardController::class, 'index']); //Listar
        Route::post('/', [RoleDashboardController::class, 'store']); //create

        // Route::prefix('{role_id}')->group(function () {

        //     Route::get('/', [RoleDashboardController::class, 'show']); //show o mostrar por id
        //     Route::put('/', [RoleDashboardController::class, 'update']); //actualizar
        //     Route::delete('/', [RoleDashboardController::class, 'destroy']); //borrar

        // });

    });

    Route::prefix('users')->group(function () {

        //usuarios que son empleados
        Route::prefix('employees')->group(function () {

            Route::get('/', [EmployeeDashboardController::class, 'index']); //Listar
            Route::get('/blocked', [EmployeeDashboardController::class, 'blocked']); //Listar
            Route::get('/search/{search?}', [EmployeeDashboardController::class, 'search']); //buscar
            Route::post('/', [EmployeeDashboardController::class, 'store']); //create

            Route::prefix('{employee_id}')->group(function () {

                Route::get('/', [EmployeeDashboardController::class, 'show']); //show o mostrar por id
                Route::put('/', [EmployeeDashboardController::class, 'update']); //actualizar
                Route::delete('/', [EmployeeDashboardController::class, 'destroy']); //borrar

            });
        });

        //Usuarios que son clientes        
        Route::prefix('customers')->group(function () {

            Route::get('/', [CustomerDashboardController::class, 'index']); //Listar
            Route::get('/blocked', [CustomerDashboardController::class, 'blocked']); //Listar
            Route::get('/search/{search?}', [CustomerDashboardController::class, 'search']); //buscar
            Route::post('/', [CustomerDashboardController::class, 'store']); //create

            Route::prefix('{customer_id}')->group(function () {

                Route::get('/', [CustomerDashboardController::class, 'show']); //show o mostrar por id
                Route::put('/', [CustomerDashboardController::class, 'update']); //actualizar
                Route::delete('/', [CustomerDashboardController::class, 'destroy']); //borrar

            });
        });

        Route::prefix('suppliers')->group(function () {

            Route::get('/', [SupplierDashboardController::class, 'index']); //Listar
            Route::get('/blocked', [SupplierDashboardController::class, 'blocked']); //Listar
            Route::get('/search/{search?}', [SupplierDashboardController::class, 'search']); //buscar
            Route::post('/', [SupplierDashboardController::class, 'store']); //create

            Route::prefix('{supplier_id}')->group(function () {

                Route::get('/', [SupplierDashboardController::class, 'show']); //show o mostrar por id
                Route::put('/', [SupplierDashboardController::class, 'update']); //actualizar
                Route::delete('/', [SupplierDashboardController::class, 'destroy']); //borrar

            });
        });

        //Todos los usuarios
        Route::get('/', [UserDashboardController::class, 'index']); //Listar
        Route::post('/', [UserDashboardController::class, 'store']); //create

        Route::prefix('{user_id}')->group(function () {

            Route::get('/', [UserDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [UserDashboardController::class, 'update']); //actualizar
            Route::delete('/', [UserDashboardController::class, 'destroy']); //borrar

        });


        //Esto se encuentra dentro de user porque quiere decir que "un user tiene un perfil de employees (empleado)"
    });



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


    Route::prefix('purchases')->group(function () {
    
        Route::get('/', [PurchaseDashboardController::class, 'index']); //Listar
        Route::post('/', [PurchaseDashboardController::class, 'store']); //create
    
        Route::prefix('{Purchase_id}')->group(function () {
    
            Route::get('/', [PurchaseDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [PurchaseDashboardController::class, 'update']); //actualizar
            Route::delete('/', [PurchaseDashboardController::class, 'destroy']); //borrar
    
        });
    
    });
});
