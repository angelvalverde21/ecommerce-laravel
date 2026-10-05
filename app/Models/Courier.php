<?php

namespace App\Models;

use App\Traits\HasStatusScopesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
/**
 * Modelo Courier
 *
 * Documentación:
 * file://./readme.md
 */

class Courier extends Model
{
    //

    protected $guarded = ['id', 'created_at'];

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class);
    }

    public function scopeSearch(Builder $query, $term)
    {

        // return $query->where('products.name', 'LIKE', '%' . $term . '%')
        //     ->orWhere('products.tags', 'LIKE', '%' . $term . '%');
        // Esto da como resultado una consulta sin los parentesis
        // WHERE products.name LIKE '%term%' OR products.tags LIKE '%term%'



        //Es mejor usar esta consulta porque encapsula el query por si se concatena con otra consulta, esta no se vera afectara
        // porque el resultado final tendra los parentesis
        // WHERE (products.name LIKE '%term%' OR products.tags LIKE '%term%'

        return $query->where(function ($query) use ($term) {
            $query->where('couriers.name', 'LIKE', '%' . $term . '%')
                ->orWhere('couriers.phone', 'LIKE', '%' . $term . '%');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 0);
    }
}
