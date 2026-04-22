<?php

// use App\Http\Controllers\api\v1\dashboard\ProductDashboardController;

use App\Http\Controllers\Api\Dashboard\AddressDashboardController;
use App\Http\Controllers\Api\Dashboard\AttendanceDashboardController;
use App\Http\Controllers\Api\Dashboard\AttributeDashboardController;
use App\Http\Controllers\Api\Dashboard\BarcodeDashboardController;
use App\Http\Controllers\Api\Dashboard\BrandDashboardController;
use App\Http\Controllers\Api\Dashboard\CategoryDashboardController;
use App\Http\Controllers\Api\Dashboard\ColorDashboardController;
use App\Http\Controllers\Api\Dashboard\CourierDashboardController;
use App\Http\Controllers\Api\Dashboard\CustomerDashboardController;
use App\Http\Controllers\Api\Dashboard\DistrictDashboardController;
use App\Http\Controllers\Api\Dashboard\EmployeeDashboardController;
use App\Http\Controllers\Api\Dashboard\GalleryDashboardController;
use App\Http\Controllers\Api\Dashboard\GatewayDashboardController;
use App\Http\Controllers\Api\Dashboard\IdentityDashboardController;
use App\Http\Controllers\Api\Dashboard\ImageDashboardController;
use App\Http\Controllers\Api\Dashboard\images\ImagePaymentController;
use App\Http\Controllers\Api\Dashboard\Images\ImageProductController;
use App\Http\Controllers\Api\Dashboard\InventoryDashboardController;
use App\Http\Controllers\Api\Dashboard\KardexDashboardController;
use App\Http\Controllers\Api\Dashboard\ManufactureDashboardController;
use App\Http\Controllers\Api\Dashboard\ManufactureKardexDashboardController;
use App\Http\Controllers\Api\Dashboard\OptionDashboardController;
use App\Http\Controllers\Api\Dashboard\OptionValueDashboardController;
use App\Http\Controllers\Api\Dashboard\PriceDashboardController;
use App\Http\Controllers\Api\Dashboard\ProductDashboardController;
use App\Http\Controllers\Api\Dashboard\ManufactureOrderDashboardController;
use App\Http\Controllers\Api\Dashboard\ManufactureProductionDashboardController;
use App\Http\Controllers\Api\Dashboard\ManufacturePurchaseDashboardController;
use App\Http\Controllers\Api\Dashboard\ManufactureVariantDashboardController;
use App\Http\Controllers\Api\Dashboard\PaymentDashboardController;
use App\Http\Controllers\Api\Dashboard\PettyCashDashboardController;
use App\Http\Controllers\Api\Dashboard\PurchaseDashboardController;
use App\Http\Controllers\Api\Dashboard\RoleDashboardController;
use App\Http\Controllers\Api\Dashboard\SizeDashboardController;
use App\Http\Controllers\Api\Dashboard\StoreDashboardController;
use App\Http\Controllers\Api\Dashboard\SupplierDashboardController;
use App\Http\Controllers\Api\Dashboard\UnitDashboardController;
use App\Http\Controllers\Api\Dashboard\UserDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Dashboard\BatchDashboardController;
use App\Http\Controllers\Api\Dashboard\EmployeeAttendanceDashboardController;
use App\Http\Controllers\Api\Dashboard\EmployeePaymentDashboardController;
use App\Http\Controllers\Api\Dashboard\InventoryBatchDashboardController;
use App\Http\Controllers\Api\Dashboard\LocationDashboardController;

// Route::prefix('v1/dashboard/{store}')->middleware(['auth:api'])->group(function () {

// });
// http://erp.test/api/v1/dashboard/sorelle/payments/1/images
Route::prefix('v1/{store}/dashboard')->middleware('api')->middleware(['auth:api'])->group(function () {

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

    Route::prefix('addresses')->group(function () {

        Route::get('/', [AddressDashboardController::class, 'index']); //Listar
        Route::post('/', [AddressDashboardController::class, 'store']); //create

        Route::prefix('{address_id}')->group(function () {

            Route::get('/', [AddressDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [AddressDashboardController::class, 'update']); //actualizar
            Route::delete('/', [AddressDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('images')->group(function () {

        Route::get('/', [ImageDashboardController::class, 'index']); //Listar
        Route::post('/', [ImageDashboardController::class, 'store']); //create

        Route::prefix('{image_id}')->group(function () {

            Route::get('/', [ImageDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [ImageDashboardController::class, 'update']); //actualizar
            Route::delete('/', [ImageDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('districts')->group(function () {

        Route::get('/', [DistrictDashboardController::class, 'index']); //Listar
        Route::post('/', [DistrictDashboardController::class, 'store']); //create
        Route::get('/search/{search?}', [DistrictDashboardController::class, 'search']); //buscar

        Route::prefix('{district_id}')->group(function () {

            Route::get('/', [DistrictDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [DistrictDashboardController::class, 'update']); //actualizar
            Route::delete('/', [DistrictDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('suppliers')->group(function () {

        Route::get('/', [SupplierDashboardController::class, 'index']); //Listar
        Route::get('/blocked', [SupplierDashboardController::class, 'blocked']); //Listar
        Route::post('/search', [SupplierDashboardController::class, 'search']); //buscar
        // Route::get('/search/{search?}', [SupplierDashboardController::class, 'search']); //buscar
        // Route::post('/search', [SupplierDashboardController::class, 'searchPost']); //buscar
        Route::post('/', [SupplierDashboardController::class, 'store']); //create

        Route::prefix('{supplier_id}')->group(function () {

            Route::get('/', [SupplierDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [SupplierDashboardController::class, 'update']); //actualizar
            Route::delete('/', [SupplierDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('manufactures')->group(function () {

        Route::get('/', [ManufactureDashboardController::class, 'index']); //Listar
        Route::post('/', [ManufactureDashboardController::class, 'store']); //create
        Route::get('/search/{search?}', [ManufactureDashboardController::class, 'search']); //buscar

        Route::prefix('productions')->group(function () {

            Route::get('/', [ManufactureProductionDashboardController::class, 'index']); //Listar
            Route::post('/', [ManufactureProductionDashboardController::class, 'store']); //create
            // Route::get('/search/{search?}', [ManufactureProductionDashboardController::class, 'search']); //buscar

            Route::prefix('{production_id}')->group(function () {

                // Route::get('/', [ManufactureProductionDashboardController::class, 'show']); //show o mostrar por id
                Route::put('/', [ManufactureProductionDashboardController::class, 'update']); //actualizar
                // Route::delete('/', [ManufactureProductionDashboardController::class, 'destroy']); //borrar

            });
        });

        Route::prefix('orders')->group(function () {

            Route::get('/', [ManufactureOrderDashboardController::class, 'index']); //Listar
            Route::post('/', [ManufactureOrderDashboardController::class, 'store']); //create
            // Route::get('/search/{search?}', [ManufactureOrderDashboardController::class, 'search']); //buscar

            Route::prefix('{order_id}')->group(function () {

                // Route::get('/', [ManufactureOrderDashboardController::class, 'show']); //show o mostrar por id
                Route::put('/', [ManufactureOrderDashboardController::class, 'update']); //actualizar
                // Route::delete('/', [ManufactureOrderDashboardController::class, 'destroy']); //borrar

            });
        });

        Route::prefix('{manufacture_id}')->group(function () {

            Route::get('/', [ManufactureDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [ManufactureDashboardController::class, 'update']); //actualizar
            Route::delete('/', [ManufactureDashboardController::class, 'destroy']); //borrar

            Route::prefix('variants')->group(function () {

                Route::get('/', [ManufactureVariantDashboardController::class, 'index']); //Listar
                Route::post('/', [ManufactureVariantDashboardController::class, 'store']); //create
                Route::post('/batch', [ManufactureVariantDashboardController::class, 'batch']); //create

                Route::prefix('{manufacture_variant_id}')->group(function () {

                    Route::put('/', [ManufactureVariantDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [ManufactureVariantDashboardController::class, 'destroy']); //borrar

                });
            });

            Route::prefix('kardexes')->group(function () {

                Route::get('/', [ManufactureKardexDashboardController::class, 'index']); //Listar
                Route::post('/', [ManufactureKardexDashboardController::class, 'store']); //create
                // Route::post('/batch', [ManufactureKardexDashboardController::class, 'batch']); //create

                Route::prefix('{manufacture_kardex_id}')->group(function () {

                    // Route::get('/', [ManufactureOrderVariantDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [ManufactureKardexDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [ManufactureKardexDashboardController::class, 'destroy']); //borrar

                });
            });

            Route::prefix('purchases')->group(function () {

                Route::get('/', [ManufacturePurchaseDashboardController::class, 'index']); //Listar
                Route::post('/', [ManufacturePurchaseDashboardController::class, 'store']); //create

                Route::prefix('{purchase_id}')->group(function () {

                    Route::get('/', [ManufacturePurchaseDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [ManufacturePurchaseDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [ManufacturePurchaseDashboardController::class, 'destroy']); //borrar

                });
            });
        });
    });

    Route::prefix('inventories')->group(function () {

        Route::get('/', [InventoryDashboardController::class, 'index']); //Listar
        Route::post('/', [InventoryDashboardController::class, 'store']); //create
        Route::post('/batch', [InventoryDashboardController::class, 'batch']);

        Route::prefix('{inventory_id}')->group(function () {

            Route::get('/', [InventoryDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [InventoryDashboardController::class, 'update']); //actualizar
            Route::delete('/', [InventoryDashboardController::class, 'destroy']); //borrar

        });
    });


    Route::prefix('locations')->group(function () {

        Route::get('/', [LocationDashboardController::class, 'index']); //Listar
        Route::post('/', [LocationDashboardController::class, 'store']); //create

        Route::prefix('{location_id}')->group(function () {

            Route::get('/', [LocationDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [LocationDashboardController::class, 'update']); //actualizar
            Route::delete('/', [LocationDashboardController::class, 'destroy']); //borrar

        });
    });


    // Route::prefix('productions')->group(function () {

    //     Route::get('/', [ProductionDashboardController::class, 'index']); //Listar
    //     Route::post('/', [ProductionDashboardController::class, 'store']); //create
    //     Route::get('/search/{search?}', [ProductionDashboardController::class, 'search']); //buscar

    //     Route::prefix('{production_id}')->group(function () {

    //         Route::get('/', [ProductionDashboardController::class, 'show']); //show o mostrar por id
    //         Route::put('/', [ProductionDashboardController::class, 'update']); //actualizar
    //         Route::delete('/', [ProductionDashboardController::class, 'destroy']); //borrar

    //         Route::prefix('purchases')->group(function () {

    //             Route::get('/', [ProductionPurchaseDashboardController::class, 'index']); //Listar
    //             Route::post('/', [ProductionPurchaseDashboardController::class, 'store']); //create

    //             Route::prefix('{ProductionPurchase_id}')->group(function () {

    //                 Route::get('/', [ProductionPurchaseDashboardController::class, 'show']); //show o mostrar por id
    //                 Route::put('/', [ProductionPurchaseDashboardController::class, 'update']); //actualizar
    //                 Route::delete('/', [ProductionPurchaseDashboardController::class, 'destroy']); //borrar

    //             });
    //         });


    //         Route::prefix('variants')->group(function () {

    //             Route::get('/', [ProductionVariantDashboardController::class, 'index']); //Listar
    //             Route::post('/', [ProductionVariantDashboardController::class, 'store']); //create
    //             Route::post('/batch', [ProductionVariantDashboardController::class, 'batch']); //create

    //             Route::prefix('{production_variant_id}')->group(function () {

    //                 // Route::get('/', [ManufactureOrderVariantDashboardController::class, 'show']); //show o mostrar por id
    //                 Route::put('/', [ProductionVariantDashboardController::class, 'update']); //actualizar
    //                 Route::delete('/', [ProductionVariantDashboardController::class, 'destroy']); //borrar

    //             });
    //         });

    //         Route::prefix('kardexes')->group(function () {

    //             Route::get('/', [ProductionKardexDashboardController::class, 'index']); //Listar
    //             Route::post('/', [ProductionKardexDashboardController::class, 'store']); //create
    //             Route::post('/batch', [ProductionKardexDashboardController::class, 'batch']); //create

    //             Route::prefix('{kardex_id}')->group(function () {

    //                 // Route::get('/', [ManufactureOrderVariantDashboardController::class, 'show']); //show o mostrar por id
    //                 Route::put('/', [ProductionKardexDashboardController::class, 'update']); //actualizar
    //                 Route::delete('/', [ProductionKardexDashboardController::class, 'destroy']); //borrar

    //             });
    //         });
    //     });
    // });

    Route::prefix('users')->group(function () {

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

    //usuarios que son empleados
    Route::prefix('employees')->group(function () {

        Route::get('/', [EmployeeDashboardController::class, 'index']); //Listar
        Route::get('/blocked', [EmployeeDashboardController::class, 'blocked']); //Listar
        // Route::get('/search/{search?}', [EmployeeDashboardController::class, 'search']); //buscar
        Route::post('/search', [EmployeeDashboardController::class, 'search']); //buscar
        Route::post('/', [EmployeeDashboardController::class, 'store']); //create

        Route::prefix('{employee_id}')->group(function () {

            Route::get('/', [EmployeeDashboardController::class, 'show']); //show o mostrar por id
            Route::get('/orders', [EmployeeDashboardController::class, 'orders']); //show o mostrar por id
            Route::post('/orders/search', [EmployeeDashboardController::class, 'ordersSearch']); //show o mostrar por id
            Route::put('/', [EmployeeDashboardController::class, 'update']); //actualizar
            Route::delete('/', [EmployeeDashboardController::class, 'destroy']); //borrar

            Route::prefix('attendances')->group(function () {

                Route::get('/', [EmployeeAttendanceDashboardController::class, 'index']); //Listar
                Route::post('/', [EmployeeAttendanceDashboardController::class, 'store']); //create
                Route::post('/upload', [EmployeeAttendanceDashboardController::class, 'upload']); //create
                Route::post('/search', [EmployeeAttendanceDashboardController::class, 'search']); //buscar
                Route::prefix('{attendance_id}')->group(function () {

                    Route::get('/', [EmployeeAttendanceDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [EmployeeAttendanceDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [EmployeeAttendanceDashboardController::class, 'destroy']); //borrar

                });
            });

            Route::prefix('payments')->group(function () {

                Route::get('/', [EmployeePaymentDashboardController::class, 'index']); //Listar
                Route::post('/', [EmployeePaymentDashboardController::class, 'store']); //create
                Route::post('/search', [EmployeePaymentDashboardController::class, 'search']); //create

                Route::prefix('{payment_id}')->group(function () {

                    Route::get('/', [EmployeePaymentDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [EmployeePaymentDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [EmployeePaymentDashboardController::class, 'destroy']); //borrar

                });
            });
        });
    });


    Route::prefix('couriers')->group(function () {

        Route::get('/', [CourierDashboardController::class, 'index']); //Listar
        Route::get('/active', [CourierDashboardController::class, 'active']); //Listar
        Route::get('/blocked', [CourierDashboardController::class, 'blocked']); //Listar
        Route::post('/', [CourierDashboardController::class, 'store']); //create
        Route::get('/search/{search?}', [CourierDashboardController::class, 'search']); //buscar

        Route::prefix('{courier_id}')->group(function () {
            Route::get('/', [CourierDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [CourierDashboardController::class, 'update']); //actualizar
            Route::delete('/', [CourierDashboardController::class, 'destroy']); //borrar
        });
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
        Route::post('/search', [ProductDashboardController::class, 'search']); //Listar
        Route::post('/', [ProductDashboardController::class, 'store']); //create

        Route::prefix('{product_id}')->group(function () {

            Route::get('/', [ProductDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [ProductDashboardController::class, 'update']); //actualizar
            Route::delete('/', [ProductDashboardController::class, 'destroy']); //borrar

            Route::prefix('images')->group(function () {

                Route::get('/', [ImageProductController::class, 'index']); //Listar
                Route::post('/', [ImageProductController::class, 'store']); //create

                Route::prefix('{brand_id}')->group(function () {

                    Route::get('/', [ImageProductController::class, 'show']); //show o mostrar por id
                    Route::put('/', [ImageProductController::class, 'update']); //actualizar
                    Route::delete('/', [ImageProductController::class, 'destroy']); //borrar

                });
            });

            Route::prefix('prices')->group(function () {

                Route::get('/', [PriceDashboardController::class, 'index']); //Listar
                Route::post('/', [PriceDashboardController::class, 'store']); //create

                Route::prefix('{price_id}')->group(function () {

                    Route::get('/', [PriceDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [PriceDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [PriceDashboardController::class, 'destroy']); //borrar

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

            //Ruta para options

            Route::prefix('options')->group(function () {

                Route::get('/', [OptionDashboardController::class, 'index']); //Listar
                Route::post('/', [OptionDashboardController::class, 'store']); //create

                Route::prefix('{option_id}')->group(function () {

                    Route::get('/', [OptionDashboardController::class, 'show']); //show o mostrar por id
                    Route::put('/', [OptionDashboardController::class, 'update']); //actualizar
                    Route::delete('/', [OptionDashboardController::class, 'destroy']); //borrar

                    Route::prefix('galleries')->group(function () {

                        Route::get('/', [GalleryDashboardController::class, 'index']); //Listar
                        Route::post('/', [GalleryDashboardController::class, 'store']); //create

                        Route::prefix('{Gallery_id}')->group(function () {

                            Route::get('/', [GalleryDashboardController::class, 'show']); //show o mostrar por id
                            Route::put('/', [GalleryDashboardController::class, 'update']); //actualizar
                            Route::delete('/', [GalleryDashboardController::class, 'destroy']); //borrar

                        });
                    });

                    //Este es option_values
                    Route::prefix('values')->group(function () {

                        Route::get('/', [OptionValueDashboardController::class, 'index']); //Listar
                        Route::post('/', [OptionValueDashboardController::class, 'store']); //create

                        Route::prefix('{option_value_id}')->group(function () {

                            Route::get('/', [OptionValueDashboardController::class, 'show']); //show o mostrar por id
                            Route::put('/', [OptionValueDashboardController::class, 'update']); //actualizar
                            Route::delete('/', [OptionValueDashboardController::class, 'destroy']); //borrar

                        });
                    });
                });
            });
        }); //fin de product_id


        //options


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

    Route::prefix('units')->group(function () {

        Route::get('/', [UnitDashboardController::class, 'index']); //Listar
        Route::post('/', [UnitDashboardController::class, 'store']); //create

        Route::prefix('{Unit_id}')->group(function () {

            Route::get('/', [UnitDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [UnitDashboardController::class, 'update']); //actualizar
            Route::delete('/', [UnitDashboardController::class, 'destroy']); //borrar

        });
    });


    Route::prefix('attendances')->group(function () {

        Route::get('/', [AttendanceDashboardController::class, 'index']); //Listar
        Route::post('/', [AttendanceDashboardController::class, 'store']); //create
        Route::post('/upload', [AttendanceDashboardController::class, 'upload']); //create

        Route::prefix('{attendance_id}')->group(function () {

            Route::get('/', [AttendanceDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [AttendanceDashboardController::class, 'update']); //actualizar
            Route::delete('/', [AttendanceDashboardController::class, 'destroy']); //borrar

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

    Route::prefix('payments')->group(function () {

        Route::get('/', [PaymentDashboardController::class, 'index']); //Listar
        Route::post('/', [PaymentDashboardController::class, 'store']); //create

        Route::prefix('{payment_id}')->group(function () {

            Route::get('/', [PaymentDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [PaymentDashboardController::class, 'update']); //actualizar
            Route::delete('/', [PaymentDashboardController::class, 'destroy']); //borrar

            Route::prefix('images')->group(function () {

                Route::get('/', [ImagePaymentController::class, 'index']); //Listar
                Route::post('/', [ImagePaymentController::class, 'store']); //create

                Route::prefix('{image_id}')->group(function () {

                    Route::get('/', [ImagePaymentController::class, 'show']); //show o mostrar por id
                    Route::put('/', [ImagePaymentController::class, 'update']); //actualizar
                    Route::delete('/', [ImagePaymentController::class, 'destroy']); //borrar

                });
            });
        });
    });

    Route::prefix('pettycashes')->group(function () {

        Route::get('/', [PettyCashDashboardController::class, 'index']); //Listar
        Route::post('/', [PettyCashDashboardController::class, 'store']); //create

        Route::prefix('{petty_cash_id}')->group(function () {

            Route::get('/', [PettyCashDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [PettyCashDashboardController::class, 'update']); //actualizar
            Route::delete('/', [PettyCashDashboardController::class, 'destroy']); //borrar

        });
    });


    Route::prefix('gateways')->group(function () {

        Route::get('/', [GatewayDashboardController::class, 'index']); //Listar
        Route::post('/', [GatewayDashboardController::class, 'store']); //create

        Route::prefix('{gateway_id}')->group(function () {

            Route::get('/', [GatewayDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [GatewayDashboardController::class, 'update']); //actualizar
            Route::delete('/', [GatewayDashboardController::class, 'destroy']); //borrar

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

    Route::prefix('kardexes')->group(function () {

        Route::get('/', [KardexDashboardController::class, 'index']); //Listar
        Route::post('/', [KardexDashboardController::class, 'store']); //create
        Route::post('/batch', [KardexDashboardController::class, 'batch']); //create
        Route::get('/variants/{variant_id?}', [KardexDashboardController::class, 'getVariants']); //buscar

        Route::prefix('{kardex_id}')->group(function () {

            Route::get('/', [KardexDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [KardexDashboardController::class, 'update']); //actualizar
            Route::delete('/', [KardexDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('batches')->group(function () {

        Route::get('/', [BatchDashboardController::class, 'index']); //Listar
        Route::post('/', [BatchDashboardController::class, 'store']); //create
        Route::post('/batch', [BatchDashboardController::class, 'batch']); //create

        Route::prefix('{batch_id}')->group(function () {

            Route::get('/', [BatchDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [BatchDashboardController::class, 'update']); //actualizar
            Route::delete('/', [BatchDashboardController::class, 'destroy']); //borrar

        });
    });

    Route::prefix('barcodes')->group(function () {

        Route::get('/', [BarcodeDashboardController::class, 'index']); //Listar
        Route::post('/print', [BarcodeDashboardController::class, 'print']); //Imprimir
        Route::post('/', [BarcodeDashboardController::class, 'store']); //create

        Route::prefix('barcode_id}')->group(function () {

            Route::get('/', [BarcodeDashboardController::class, 'show']); //show o mostrar por id
            Route::put('/', [BarcodeDashboardController::class, 'update']); //actualizar
            Route::delete('/', [BarcodeDashboardController::class, 'destroy']); //borrar

        });
    });
});
