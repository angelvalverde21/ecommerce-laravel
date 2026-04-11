<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{

    protected $guarded = ['id', 'created_at'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function kardexes()
    {
        return $this->morphMany(Kardex::class, 'kardexable')->orderBy('id', 'desc');
    }
}
