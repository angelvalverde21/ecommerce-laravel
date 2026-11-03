<?php

namespace App\Services\Dashboard\Product;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductService
{
    public function index(Store $store, int $perPage = 20, string $sort = 'recent', ?Request $request = null)
    {
        $request ??= request();
        $query = $store->products()->withStockTotal()->with('category');

        return $this->sortProducts($query->getQuery(), $request, $sort)
            ->paginate($perPage)
            ->appends($request->query());
    }

    public function search(Store $store, Request $request, int $perPage = 20)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = $store->products()->withStockTotal()->with([
            'category', 'image', 'variants.product.image',
            'variants.variant_option_values.optionValue',
        ]);

        $search = trim($validated['search'] ?? '');
        if ($search !== '') {
            $query->search($search);
        }
        if (!empty($validated['start_date'])) {
            $query->where('products.created_at', '>=', $validated['start_date'].' 00:00:00');
        }
        if (!empty($validated['end_date'])) {
            $query->where('products.created_at', '<=', $validated['end_date'].' 23:59:59');
        }

        return $this->sortProducts($query->getQuery(), $request)
            ->paginate($perPage)
            ->appends($request->only(['search', 'start_date', 'end_date', 'sort', 'sort_by', 'sort_direction']));
    }

    private function sortProducts(Builder $query, Request $request, string $legacySort = 'recent'): Builder
    {
        $validated = $request->validate([
            'sort' => 'sometimes|in:stock,recent',
            'sort_by' => 'sometimes|in:id,name,category,created_at,stock_total,price',
            'sort_direction' => 'sometimes|in:asc,desc',
        ]);

        $field = $validated['sort_by'] ?? (($validated['sort'] ?? $legacySort) === 'stock' ? 'stock_total' : 'created_at');
        $direction = $validated['sort_direction'] ?? 'desc';
        $query->reorder();

        if ($field === 'category') {
            $query->orderBy(Category::query()->select('name')
                ->whereColumn('categories.id', 'products.category_id')->limit(1), $direction);
        } elseif ($field === 'stock_total') {
            // Treat products without variants as zero, just like the JSON accessor.
            $stock = $query->getModel()->variants()->getRelated()->newQuery()
                ->selectRaw('COALESCE(SUM(stock), 0)')
                ->whereColumn('variants.product_id', 'products.id');
            $query->orderBy($stock, $direction);
        } else {
            $query->orderBy('products.'.$field, $direction);
        }

        if ($field !== 'id') {
            $query->orderByDesc('products.id');
        }

        return $query;
    }
}
