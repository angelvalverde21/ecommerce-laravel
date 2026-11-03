<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'variants',
        'courier_address_id',
        'delivery_cost',
        'is_cod',
        'freight_collect',
        'subtotal',
        'igv',
        'total',
    ];

    protected $casts = [
        'variants' => 'array',
        'is_cod' => 'boolean',
        'freight_collect' => 'boolean',
        'delivery_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function courierAddress()
    {
        return $this->belongsTo(Address::class, 'courier_address_id');
    }   
}
