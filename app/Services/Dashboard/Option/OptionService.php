<?php

namespace App\Services\Dashboard\Option;

use App\Exceptions\CustomException;
use App\Models\Store;
use App\Models\Option;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class OptionService
{

    public function store(Store $store, int $productId, string $optionName)
    {

        Log::info('Resolviendo opción', [
            'store_id'   => $store->id,
            'product_id' => $productId,
            'name'       => $optionName,
        ]);

        // 1️⃣ Validar que el producto pertenezca al store
        $product = $store->products()
            ->where('id', $productId)
            ->firstOrFail();

        $optionName = trim($optionName);

        // 2️⃣ Obtener defaults sin collect()
        $defaults = Option::DEFAULT[$optionName] ?? [];

        // 3️⃣ Crear o recuperar opción usando relación
        $option = $product->options()->firstOrCreate(
            [
                'store_id'   => $store->id,
                'product_id' => $product->id,
                'name'       => $optionName,
            ],
            [
                'label'      => $defaults['label'] ?? null,
                'sort_order' => $defaults['sort_order'] ?? 1,
            ]
        );

        return $option->load('option_values');
        
    }
}
