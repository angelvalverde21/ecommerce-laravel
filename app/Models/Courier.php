<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'status',
        'is_cod',
        'freight_collect',
        'delivery_type',
    ];

    protected $hidden = [
        'user_id',
        'is_cash_on_delivery',
        'is_freight_collect',
        'is_express_shipping',
    ];

    protected $casts = [
        'status' => 'integer',
        'is_cod' => 'boolean',
        'freight_collect' => 'boolean',
    ];

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $query) use ($term) {
            $query->where('couriers.name', 'LIKE', '%' . $term . '%')
                ->orWhere('couriers.phone', 'LIKE', '%' . $term . '%');
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('status', 0);
    }
}