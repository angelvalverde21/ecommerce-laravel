<?php

namespace App\Models;

use App\Models\Scopes\ActiveVariantScope;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    //
    protected $fillable = [
        'product_id',
        'title',
        'sku',
        'price',
        'stock',
        'status',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ActiveVariantScope);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Valores de opción que componen la variante
     * Ej: Negro, M
     */
    public function optionValues()
    {
        return $this->belongsToMany(
            OptionValue::class,
            'variant_option_values'
        )->with('option');
    }

    public function variant_option_values()
    {
        return $this->hasMany(VariantOptionValue::class);
    }

    public function kardexes()
    {
        return $this->hasMany(Kardex::class);
    }

    public function manufactureKardexes()
    {
        return $this->hasMany(Kardex::class)
            ->where('kardexable_type', Manufacture::class);
    }
}
