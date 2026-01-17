<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    /** @use HasFactory<\Database\Factories\SizeFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class);
    }

    public function prices()
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    // protected $fillable = [
    //     'type',        // etiqueta | blackfriday | feria | live | etc
    //     'value',       // valor del precio
    //     'currency',    // PEN, USD, etc
    // ];


}
