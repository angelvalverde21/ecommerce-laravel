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

    public function show(Store $store, $manufacture_id, bool $includeKardex = true, bool $summaryOnly = false): Manufacture
    {
        $query = $store->manufactures()
            ->withFinancialSummary()
            ->withReceptionSummary()
            ->withDeliveryTrackingCount();
        if (!$summaryOnly) {
            $query->with('payments');
            if ($includeKardex) $query->withVariantsAndKardexes();
            else $query->with(['variants.product.image', 'variants.optionValues']);
        }
        return $query->findOrFail($manufacture_id);
    }

    public function variants(Store $store, int $manufacture_id, bool $includeKardex = true)
    {
        //

        $query = $store->manufactures()
            ->findOrFail($manufacture_id)
            ->manufactureVariants()
            ->with([
                'variant.product.image',
                'variant.optionValues',
            ]);
        if ($includeKardex) $query->with(['variant.manufactureKardexes' => fn($q) => $q->where('kardexable_id', $manufacture_id)]);
        return $query->get();
    }
}
