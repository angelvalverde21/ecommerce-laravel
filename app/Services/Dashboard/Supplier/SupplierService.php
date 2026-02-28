<?php

namespace App\Services\Dashboard\Supplier;

use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SupplierService
{
    /**
     * Buscar Suppliers relacionados a un store con filtro
     */
    public function search(Store $store, Request $request, int $perPage = 20)
    {

        $search = $request->input('search', '');

        if (trim($search) === '' || $search === null) {
            Log::info("se ejectutó la búsqueda sin término de búsqueda, se devolverá el listado completo de Suppliers para el store con id: {$store->id}");
            return $this->index($store);
        }

        $validated = $request->validate([
            'search'     => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',

        ]);

        $search     = $validated['search'];
        $startDate  = $validated['start_date'] ?? null;
        $endDate    = $validated['end_date'] ?? null;

        $query = $store->suppliers()
            ->whereHas('user', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('document_number', 'LIKE', "%{$search}%");
                });
            })
            ->with('user');


        if ($startDate && $endDate) {
            $query->whereBetween('suppliers.created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59'
            ]);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener todos los Suppliers del store
     */
    public function index(Store $store, int $perPage = 20)
    {
        //Aqui ya service ya tiene el modelo que le hemos pasado, en este caso Supplier

        return $store->suppliers()
            ->with('user')
            ->paginate($perPage)->withQueryString();
    }

    /**
     * Obtener Suppliers activos
     */
    public function active(Store $store, int $perPage = 20)
    {
        return $store->suppliers()
            ->active() // ahora funciona porque el scope revisa si hay relación user
            ->with('user')
            ->orderByDesc('suppliers.created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Obtener Suppliers bloqueados
     */
    public function blocked(Store $store, int $perPage = 20) //Recuerda que el active o blocked estan en un Trait HasStatusScopesTrait
    {
        return $store->suppliers()
            ->blocked() // ahora funciona porque el scope revisa si hay relación user
            ->with('user')
            ->orderByDesc('suppliers.created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Mostrar un Supplier específico del store
     */
    public function show(Store $store, int $supplier_id)
    {

        return $store->suppliers() // método del modelo Store que devuelve el Builder
            ->with(['user', 'addresses.district'])
            ->where('id', $supplier_id) // filtra el supplier específico
            ->firstOrFail(); // 404 si no existe o no pertenece a la tienda

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
