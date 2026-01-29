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
        $query = Courier::with('user')
            ->whereHas('user', function (Builder $q) use ($store, $search) {

                $q->active()
                  ->whereHas('stores', function (Builder $sq) use ($store) {
                      $sq->where('stores.id', $store->id);
                  });

                if (trim($search) !== '') {
                    $q->where(function (Builder $qq) use ($search) {
                        $qq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%")
                           ->orWhere('document_number', 'like', "%{$search}%");
                    });
                }
            });

        return FlatCourierUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Obtener todos los couriers del store
     */
    public function index(Store $store, int $perPage = 20)
    {
        $query = Courier::with(['user'])
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->whereHas('stores', function (Builder $sq) use ($store) {
                    $sq->where('stores.id', $store->id);
                });
            });

        return FlatCourierUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Obtener couriers activos
     */
    public function active(Store $store, int $perPage = 20) //Recuerda que el active o bloqued estan en un Trait HasStatusScopesTrait
    {
        $query = Courier::with('user')
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->active()
                  ->whereHas('stores', function (Builder $sq) use ($store) {
                      $sq->where('stores.id', $store->id);
                  });
            });

        return FlatCourierUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Obtener couriers bloqueados
     */
    public function blocked(Store $store, int $perPage = 20)//Recuerda que el active o blocked estan en un Trait HasStatusScopesTrait
    {
        $query = Courier::blocked()
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->whereHas('stores', function (Builder $sq) use ($store) {
                    $sq->where('stores.id', $store->id);
                });
            });

        return FlatCourierUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Mostrar un courier específico del store
     */
    public function show(Store $store, int $id)
    {
        $courier = Courier::with('user.addresses.district')
            ->where('id', $id)
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->whereHas('stores', function (Builder $sq) use ($store) {
                    $sq->where('stores.id', $store->id);
                });
            })
            ->firstOrFail();

        Log::info($courier);

        return new FlatCourierUserResource($courier);
    }

    /**
     * Actualizar un courier
     */
    public function update(Store $store, int $id, array $data)
    {
        return DB::transaction(function () use ($store, $id, $data) {

            $courier = Courier::with('user')
                ->where('id', $id)
                ->whereHas('user', function (Builder $q) use ($store) {
                    $q->whereHas('stores', function (Builder $sq) use ($store) {
                        $sq->where('stores.id', $store->id);
                    });
                })
                ->firstOrFail();

            $courier->user->update($data);

            return new FlatCourierUserResource(
                $courier->fresh(['user'])
            );
        });
    }
}
