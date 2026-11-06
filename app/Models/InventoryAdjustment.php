<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $guarded = ['id', 'created_at'];
    protected $casts = ['system_quantity'=>'integer', 'counted_quantity'=>'integer', 'difference'=>'integer', 'approved_at'=>'datetime', 'applied_at'=>'datetime'];
    public function inventory() { return $this->belongsTo(Inventory::class); }
    public function variant() { return $this->belongsTo(Variant::class)->withoutGlobalScope(\App\Models\Scopes\ActiveVariantScope::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function kardexes() { return $this->morphMany(Kardex::class, 'kardexable'); }
}
