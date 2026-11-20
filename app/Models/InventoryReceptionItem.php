<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReceptionItem extends Model
{
    protected $guarded = ['id', 'created_at'];
    protected $casts = ['quantity' => 'integer', 'options' => 'array'];
    public function reception() { return $this->belongsTo(InventoryReception::class, 'inventory_reception_id'); }
    public function kardexes() { return $this->hasMany(Kardex::class); }
}
