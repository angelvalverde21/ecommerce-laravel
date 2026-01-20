<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{

    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    protected $appends = [
        'attributes_init',
        'options_init',
        'prices_init',
    ];

    public function scopeSearch(Builder $query, $term)
    {

        // return $query->where('products.name', 'LIKE', '%' . $term . '%')
        //     ->orWhere('products.tags', 'LIKE', '%' . $term . '%');
        // Esto da como resultado una consulta sin los parentesis
        // WHERE products.name LIKE '%term%' OR products.tags LIKE '%term%'



        //Es mejor usar esta consulta porque encapsula el query por si se concatena con otra consulta, esta no se vera afectara
        // porque el resultado final tendra los parentesis
        // WHERE (products.name LIKE '%term%' OR products.tags LIKE '%term%')

        return $query->where(function ($query) use ($term) {
            $query->where('products.name', 'LIKE', '%' . $term . '%');
            // ->orWhere('products.tags', 'LIKE', '%' . $term . '%');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    
    public function prices()
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function sizes()
    {
        return $this->hasMany(Size::class)->orderBy('sort_order', 'ASC');
    }


    public function colors()
    {
        return $this->hasMany(Color::class)->orderBy('sort_order', 'ASC');
    }

    public function attributes()
    {
        return $this->hasMany(Attribute::class);
    }


    public function getAttributesInitAttribute()
    {
        return Attribute::DEFAULT_OPTIONS;
    }

    public function getOptionsInitAttribute()
    {
        return Option::DEFAULT_OPTIONS;
    }

    public function getPricesInitAttribute()
    {
        return Price::DEFAULT_OPTIONS;
    }


    /******************************************** */

    public function variants()
    {
        return $this->hasMany(Variant::class)->orderBy('sku','desc');
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }
}
