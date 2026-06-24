<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Manufacture;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufactureService
{

    public function index(Store $store, $manufacture_id) {}

    // public function show(Store $store, $manufacture_id)
    // {
    //     //
    //     $manufacture = $store->manufactures()->with([
    //         'kardexes' => function ($k) {
    //             $k->with(['variant.product.image', 'variant.optionValues']);
    //         },
    //         'user',
    //         'payments' => function ($p) {
    //             $p->with(['gateway', 'images']);
    //         },
    //         'manufactureVariants.variant' => function ($q) {
    //             $q->with([
    //                 'product.image',
    //                 'optionValues',
    //             ]);
    //         },
    //     ])
    //         ->findOrFail($manufacture_id);

    //     return $manufacture;
    // }

    public function show(Store $store, $manufacture_id): Manufacture
    {
        return $store->manufactures()->with('payments')
            ->withFinancialSummary()
            ->withVariantsAndKardexes()
            ->findOrFail($manufacture_id);
    }
}
