<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class ManufactureVariant extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    protected $table = 'manufacture_variant';


    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
    public function manufacture()
    {
        return $this->belongsTo(Manufacture::class);
    }


    public function scopeVariantData(
        Builder $query,
        int $manufacture_id
    ) {
        return $query->with([
            'variant.product.image',
            'variant.optionValues',
            'variant.manufactureKardexes' => fn($q) =>
            $q->where('kardexable_id', $manufacture_id)
                ->where('kardexable_type', Manufacture::class),
        ]);
    }
}
