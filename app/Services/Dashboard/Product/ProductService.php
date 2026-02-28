<?php

namespace App\Services\Dashboard\Product;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{

    public function index(Store $store, int $perPage = 20)
    {

        // laravel recibe internamente el page  dentro del request que mandamos de angular, por lo que no es necesario calcular el offset manualmente
        // sin embargo no es asi con perPage, por lo que si queremos un valor por defecto de 20, debemos manejarlo nosotros mismos, ya sea desde el controlador o desde el servicio

        $products = $store->products()
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $products;
    }

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

        $query = $store->products()->with(['image', 'variants.product', 'variants.variant_option_values.optionValue'])->search($search);


        if ($startDate && $endDate) {
            $query->whereBetween('products.created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59'
            ]);
        }

        return $query->paginate($perPage);
    }
}
