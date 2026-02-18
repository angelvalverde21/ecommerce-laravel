<?php

namespace App\Services\Dashboard\OptionValue;

use App\Exceptions\CustomException;
use App\Models\Store;
use App\Models\Option;
use App\Models\OptionValue;
use App\Services\Results\OptionValueResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptionValueService
{
    public function store2(Store $store, int $option_id, string $option_value): OptionValue
    {

        Log::info('Creando OptionValue', [
            'store_id'  => $store->id,
            'option_id' => $option_id,
            'value'     => $option_value,
        ]);

        DB::beginTransaction();

        try {

            // 1️⃣ Validar que la opción exista y pertenezca al store
            $option = Option::where('id', $option_id)
                ->where('store_id', $store->id)
                ->first();

            if (!$option) {
                throw new CustomException('Opción no encontrada', 404);
            }

            // 2️⃣ Crear solo si no existe
            $optionValue = OptionValue::firstOrCreate(
                [   //Estos campos se usan para buscar un registro existente, si se encuentra un registros con estos valores lo devuelve sin crear uno nuevo
                    'option_id' => $option_id,
                    'value'     => $option_value,
                ],
                [
                    'sort_order' => 1,
                ]
            );

            DB::commit();

            return $optionValue->load('option');
        } catch (\Throwable $e) {

            DB::rollBack();
            throw $e;
        }
    }

    public function store(Store $store, int $option_id, string $option_value): OptionValue
    {

        Log::info('Creando OptionValue', [
            'store_id'  => $store->id,
            'option_id' => $option_id,
            'value'     => $option_value,
        ]);

        // 1️⃣ Validar que la opción exista y pertenezca al store
        $option = Option::where('id', $option_id)
            ->where('store_id', $store->id)
            ->firstOrFail();

        // 2️⃣ Crear solo si no existe
        $optionValue = $option->optionValues()->firstOrCreate(
            [
                'value' => $option_value,
            ],
            [
                'sort_order' => 1,
            ]
        );

        return $optionValue->load('option');
    }
}
