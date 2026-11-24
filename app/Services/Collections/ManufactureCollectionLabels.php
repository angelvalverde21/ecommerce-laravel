<?php
namespace App\Services\Collections;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\{DB, Schema};

class ManufactureCollectionLabels
{
    public function attach(Collection $manufactures, int $storeId): Collection
    {
        $labels = collect();
        if ($manufactures->isNotEmpty() && Schema::hasTable('collections_processes') && Schema::hasTable('collections')) {
            $labels = DB::table('collections_processes as cp')->join('collections as c', 'c.id', '=', 'cp.collection_id')
                ->where('cp.store_id', $storeId)->where('c.store_id', $storeId)->where('cp.source_type', 'manufacture')
                ->whereIn('cp.source_id', $manufactures->modelKeys())->orderBy('cp.id')
                ->get(['cp.source_id', 'c.id', 'c.name'])->keyBy('source_id');
        }
        return $manufactures->each(function ($manufacture) use ($labels, $storeId) {
            $collection = (int) $manufacture->store_id === $storeId ? $labels->get($manufacture->id) : null;
            $manufacture->setAttribute('collection', $collection ? ['id' => (int) $collection->id, 'name' => $collection->name] : null);
        });
    }
}
