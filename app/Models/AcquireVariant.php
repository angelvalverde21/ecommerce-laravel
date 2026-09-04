<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class AcquireVariant extends Model
{
    protected $guarded = ['id', 'created_at'];

    protected $table = 'acquire_variants';


    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
    public function acquire()
    {
        return $this->belongsTo(Acquire::class);
    }


    public function scopeVariantData(
        Builder $query,
        int $acquire_id
    ) {
        return $query->with([
            'variant.product.image',
            'variant.optionValues',
            'variant.acquireKardexes' => fn($q) =>
            $q->where('kardexable_id', $acquire_id)
                ->where('kardexable_type', Acquire::class),
        ]);
    }
}
