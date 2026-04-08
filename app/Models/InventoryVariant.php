<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryVariant extends Model
{
    //

    protected $table = 'inventory_variant';

    protected $guarded = ['id', 'created_at'];

     public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

     public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
