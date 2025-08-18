<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

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
            $query->where('suppliers.name', 'LIKE', '%' . $term . '%')
                ->orWhere('suppliers.phone', 'LIKE', '%' . $term . '%');
        });
    }
}
