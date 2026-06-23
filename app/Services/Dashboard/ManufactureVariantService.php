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

    public function show(Store $store, int $manufacture_id)
    {
        //

        $manufactureVariants = $store->manufactures()
            ->findOrFail($manufacture_id)
            ->manufactureVariants()
            ->with([
                'variant.product.image',
                'variant.optionValues',
                'variant.manufactureKardexes' => fn($q) =>
                $q->where('kardexable_id', $manufacture_id)
            ])
            ->get();

        return responseOk($manufactureVariants, "Listado de ManufactureVariants obtenido correctamente");
    }
}
