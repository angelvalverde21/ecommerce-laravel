<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    //

    protected $guarded = ['id', 'created_at'];
    protected $table = 'inventory_items';

     public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

     public function variant()
    {
        return $this->belongsTo(Variant::class);
    }


}
