<?php

namespace App\Services\Dashboard\Supplier;

use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierService
{
    /**
     * Buscar Suppliers relacionados a un store con filtro
     */
    public function search(Store $store, string $search, int $perPage = 50)
    {
        $query = Supplier::with('user')
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

        return FlatSupplierUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Obtener todos los Suppliers del store
     */
    public function index(Store $store, int $perPage = 20)
    {
        $query = Supplier::with(['user'])
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->whereHas('stores', function (Builder $sq) use ($store) {
                    $sq->where('stores.id', $store->id);
                });
            });

        return FlatSupplierUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Obtener Suppliers activos
     */
    public function active(Store $store, int $perPage = 20) //Recuerda que el active o bloqued estan en un Trait HasStatusScopesTrait
    {
        $query = Supplier::with('user')
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->active()
                  ->whereHas('stores', function (Builder $sq) use ($store) {
                      $sq->where('stores.id', $store->id);
                  });
            });

        return FlatSupplierUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Obtener Suppliers bloqueados
     */
    public function blocked(Store $store, int $perPage = 20)//Recuerda que el active o blocked estan en un Trait HasStatusScopesTrait
    {
        $query = Supplier::blocked()
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->whereHas('stores', function (Builder $sq) use ($store) {
                    $sq->where('stores.id', $store->id);
                });
            });

        return FlatSupplierUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Mostrar un Supplier específico del store
     */
    public function show(Store $store, int $id)
    {
        $Supplier = Supplier::with('user.addresses.district')
            ->where('id', $id)
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->whereHas('stores', function (Builder $sq) use ($store) {
                    $sq->where('stores.id', $store->id);
                });
            })
            ->firstOrFail();

        Log::info($Supplier);

        return new FlatSupplierUserResource($Supplier);
    }

    /**
     * Actualizar un Supplier
     */
    public function update(Store $store, int $id, array $data)
    {
        return DB::transaction(function () use ($store, $id, $data) {

            $Supplier = Supplier::with('user')
                ->where('id', $id)
                ->whereHas('user', function (Builder $q) use ($store) {
                    $q->whereHas('stores', function (Builder $sq) use ($store) {
                        $sq->where('stores.id', $store->id);
                    });
                })
                ->firstOrFail();

            $Supplier->user->update($data);

            return new FlatSupplierUserResource(
                $Supplier->fresh(['user'])
            );
        });
    }
}
