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
        Log::info('Creando opción', [
            'store_id'   => $store->id,
            'product_id' => $productId,
            'name'       => $optionName,
        ]);

        DB::beginTransaction();

        try {

            // 1️⃣ Validar que el producto exista y pertenezca al store
            $product = Product::where('id', $productId)
                ->where('store_id', $store->id)
                ->first();

            Log::info("se encontro el product: " . ($product ? 'SI' : 'NO'));

            if (!$product) {
                throw new CustomException('Producto no encontrado', 404);
            }

            // 2️⃣ Evitar opciones duplicadas
            $exists = Option::where('store_id', $store->id)
                ->where('product_id', $productId)
                ->where('name', $optionName)
                ->exists();

            if (!$exists) {

                Log::info("No se encontró la opción, se creará una nueva.");

                // 3️⃣ Obtener defaults seguros
                $defaults = collect(Option::DEFAULT_OPTIONS)
                    ->keyBy('name')
                    ->get($optionName, []);

                // 4️⃣ Crear opción
                $option = Option::create([
                    'store_id'   => $store->id,
                    'product_id' => $productId,
                    'name'       => $optionName,
                    'label'      => $defaults['label'] ?? null,
                    'sort_order' => $defaults['sort_order'] ?? 1,
                ]);
            } else {

                Log::info("La opción ya existe para este producto, no se creará una nueva.");

                return false;
                // throw new CustomException('La opción ya existe para este producto', 409);

            }

            DB::commit();

            return $option;
        } catch (\Throwable $e) {

            Log::info("Error al crear la opción: " . $e->getMessage());

            DB::rollBack();

            // Re-lanzar la excepción para que la maneje Laravel
            throw $e;
        }
    }
}
