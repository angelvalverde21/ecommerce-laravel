<?php

namespace App\Services\Dashboard\Courier;

use App\Models\Store;
use App\Models\Courier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierService
{
    /**
     * Buscar couriers relacionados a un store con filtro
     */
    public function search(Store $store, string $search, int $perPage = 50)
    {
        return Courier::search($search)->get();
    }

    /**
     * Obtener todos los couriers del store
     */
    public function index(Store $store, int $perPage = 20)
    {
        $query = Courier::with(['addresses']);

        return $query->paginate($perPage);
    }

    public function agency(Store $store, int $perPage = 20)
    {
        $query = Courier::with('addresses')->where('delivery_type', 'agency');

        return $query->paginate($perPage);
    }

    public function express(Store $store, int $perPage = 20)
    {
        $query = Courier::with('addresses')->where('delivery_type', 'express');

        return $query->paginate($perPage);
    }

    /**
     * Obtener couriers activos
     */
    public function active(Store $store, int $perPage = 20) //Recuerda que el active o bloqued estan en un Trait HasStatusScopesTrait
    {
        return Courier::active()->get();
    }

    /**
     * Obtener couriers bloqueados
     */
    public function blocked(Store $store, int $perPage = 20) //Recuerda que el active o bloqued estan en un Trait HasStatusScopesTrait
    {
        return Courier::blocked()->get();
    }

    /**
     * Mostrar un courier específico del store
     */
    public function show(Store $store, int $id)
    {
        $courier = Courier::with([
                        'addresses' => function ($q) {
                            $q->with('district');
                        }
                    ])
                    ->where('id', $id)
                    ->firstOrFail();

        Log::info($courier);

        return $courier;
    }

    /**
     * Actualizar un courier
     */
    public function update(Store $store, int $id, array $data)
    {
            $courier = Courier::findOrFail($id);

            $courier->update($data);

            return $courier;
    }
}
