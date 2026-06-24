<?php

namespace App\Services\Dashboard;

use App\Exceptions\CustomException;
use App\Models\Store;
use App\Models\Option;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ManufactureVariantService
{

    public function show(Store $store, int $manufacture_id, int $manufacture_variant_id)
    {
        //
        $manufacture = $store->manufactures()
            ->findOrFail($manufacture_id);

        $manufactureVariant = $manufacture
            ->manufactureVariants()
            ->variantData($manufacture)
            ->findOrFail($manufacture_variant_id);
    }
}
