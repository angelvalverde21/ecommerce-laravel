<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PriceLabel;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PriceDashboardController extends Controller
{
    public function labels(Store $store, Request $request)
    {
        $data = $request->validate(['search' => 'nullable|string|max:255']);
        $search = trim($data['search'] ?? '');
        $labels = PriceLabel::query()->when($search !== '', fn ($query) => $query->where(fn ($query) =>
            $query->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')
        ))->orderBy('name')->limit(30)->get(['id', 'name', 'code']);

        return responseOk($labels, 'Nombres de precios obtenidos correctamente');
    }

    public function index(Store $store, $product_id, Request $request)
    {
        $product = $store->products()->findOrFail($product_id);
        $filter = $request->validate(['label_code' => 'nullable|string|max:255']);
        $prices = $product->prices()->with('label')->when($filter['label_code'] ?? null, fn ($query, $code) =>
            $query->whereHas('label', fn ($query) => $query->where('code', $code))
        )->orderByDesc('id')->get();

        return responseOk($prices, 'Precios obtenidos correctamente');
    }

    public function store(Store $store, $product_id, Request $request)
    {
        $product = $store->products()->findOrFail($product_id);
        $data = $this->validatedPrice($request);
        $price = DB::transaction(function () use ($product, $data) {
            $product->newQuery()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $data = $this->withLabel($data, $product);
            return $product->prices()->create($data);
        });

        return responseOk($price->load('label'), 'Precio creado correctamente');
    }

    public function show(Store $store, $product_id, $price_id)
    {
        $product = $store->products()->findOrFail($product_id);

        return responseOk($product->prices()->with('label')->findOrFail($price_id), 'Precio obtenido correctamente');
    }

    public function update(Store $store, $product_id, $price_id, Request $request)
    {
        $product = $store->products()->findOrFail($product_id);
        $price = $product->prices()->findOrFail($price_id);
        $data = $this->validatedPrice($request);
        DB::transaction(function () use ($product, $price, $data) {
            $product->newQuery()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $price->update($this->withLabel($data, $product, $price->id));
        });

        return responseOk($price->fresh()->load('label'), 'Precio actualizado correctamente');
    }

    public function destroy(Store $store, $product_id, $price_id)
    {
        $product = $store->products()->findOrFail($product_id);
        $price = $product->prices()->findOrFail($price_id);
        $price->delete();

        return responseOk([], 'Precio eliminado correctamente');
    }

    private function withLabel(array $data, Product $product, ?int $priceId = null): array
    {
        $label = PriceLabel::forName($data['name']);
        $exists = $product->prices()->where(fn ($query) => $query
            ->where('price_label_id', $label->id)->orWhere('name', $label->name)
        )->when($priceId !== null, fn ($query) => $query->where('id', '!=', $priceId))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => 'Este producto ya tiene un precio con esa etiqueta.']);
        }
        return array_merge($data, ['name' => $label->name, 'price_label_id' => $label->id]);
    }

    private function validatedPrice(Request $request): array
    {
        $request->merge(['name' => is_string($request->name) ? trim($request->name) : $request->name]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:999999999.999', 'decimal:0,3'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'total' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99', 'decimal:0,2'],
        ]);

        $total = round((float) $data['quantity'] * (float) $data['price'], 2);
        if ($total > 9999999999999999.99 || abs((float) $data['total'] - $total) > 0.001) {
            throw ValidationException::withMessages(['total' => 'El total debe ser cantidad por precio, redondeado a dos decimales.']);
        }

        $data['total'] = $total;
        $data['value'] = $data['price'];

        return $data;
    }
}
