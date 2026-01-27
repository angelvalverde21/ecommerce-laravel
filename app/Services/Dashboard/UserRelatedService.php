<?php

namespace App\Services\Dashboard;

use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\FlatUserResource;
use Illuminate\Support\Facades\DB;

class UserRelatedService
{
    protected string $modelClass;

    /**
     * Constructor: pasas el modelo (Supplier, Courier, etc)
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Buscar registros relacionados a un store con filtro
     */
    public function search(Store $store, string $search, int $perPage = 50)
    {
        $query = ($this->modelClass)::with('user')
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

        return FlatUserResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Obtener todos los registros relacionados a un store
     */
    public function index(Store $store, int $perPage = 20)
    {
        $query = ($this->modelClass)::with('user')
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->whereHas('stores', function (Builder $sq) use ($store) {
                        $sq->where('stores.id', $store->id);
                    });
            });

        return FlatUserResource::collection(
            $query->paginate($perPage)
        );
    }

    public function active(Store $store, int $perPage = 20)
    {
        $query = ($this->modelClass)::with('user')
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->active()
                    ->whereHas('stores', function (Builder $sq) use ($store) {
                        $sq->where('stores.id', $store->id);
                    });
            });

        return FlatUserResource::collection(
            $query->paginate($perPage)
        );
    }

    public function blocked(Store $store, int $perPage = 20)
    {
        $query = ($this->modelClass)::blocked()
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->where('store_id', $store->id);
            });

        return FlatUserResource::collection($query->paginate($perPage));
    }

    public function show(Store $store, int $id)
    {

        // Busca un registro por su ID dentro del modelo dinámico ($this->modelClass),
        // carga la relación `user` para evitar consultas N+1 y valida que dicho usuario
        // pertenezca a la tienda actual mediante la tabla pivot `store_user`.
        // Si el registro no existe o el usuario no está asociado a la tienda,
        // se lanza automáticamente una excepción 404 (firstOrFail).

        $model = ($this->modelClass)::with('user')
            ->where('id', $id)
            ->whereHas('user', function (Builder $q) use ($store) {
                $q->whereHas('stores', function (Builder $sq) use ($store) {
                    $sq->where('stores.id', $store->id);
                });
            })
            ->firstOrFail();

        return new FlatUserResource($model);
    }

    public function update(Store $store, int $id, array $data)
    {
        return DB::transaction(function () use ($store, $id, $data) {

            // Buscar el modelo asegurando que el user pertenezca al store
            $model = ($this->modelClass)::with('user')
                ->where('id', $id)
                ->whereHas('user', function (Builder $q) use ($store) {
                    $q->whereHas('stores', function (Builder $sq) use ($store) {
                        $sq->where('stores.id', $store->id);
                    });
                })
                ->firstOrFail();

            // Actualizar solo los campos enviados (aplanado)
            $model->user->update($data);

            return new FlatUserResource($model->fresh(['user']));
        });
    }

    
}
