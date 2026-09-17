<?php

namespace App\Services\Dashboard;

use App\Exceptions\CustomException;
use App\Models\Store;
use App\Models\Option;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ManufactureKardexService
{
    private array $relations = [
        'variant.product.image',
        'variant.optionValues',
    ];

    public function index(Store $store, int $manufacture_id)
    {
        $manufacture = $store->manufactures()->findOrFail($manufacture_id);

        $kardexes = $manufacture->kardexes()
            ->with($this->relations)
            ->orderByDesc('created_at')
            ->get();

        return $this->groupByDate($kardexes);
        // return $kardexes;
    }

    public function show(Store $store, int $manufacture_id, int $kardex_id)
    {
        $manufacture = $store->manufactures()->findOrFail($manufacture_id);

        $kardex = $manufacture->kardexes()
            ->with($this->relations)
            ->findOrFail($kardex_id);

        return $kardex;
    }

    private function groupByDate($kardexes)
    {
        // return $kardexes
        //     ->groupBy(fn ($item) => $item->created_at->format('Y-m-d'))
        //     ->map(fn ($items, $date) => [
        //         'date' => $date,
        //         'items' => $items->values(),
        //     ])
        //     ->values();
        return $kardexes
            ->groupBy(fn($item) => $item->created_at->format('Y-m-d'))
            ->map(fn($items, $date) => [
                'date' => $date,
                'groups' => $items
                    ->groupBy('direction')
                    ->map(fn($directionItems, $direction) => [
                        'direction' => $direction,
                        'total_quantity' => $directionItems->sum('quantity'),
                        'kardexes' => $directionItems->values(),
                    ])
                    ->values(),
            ])
            ->values();
    }
}
