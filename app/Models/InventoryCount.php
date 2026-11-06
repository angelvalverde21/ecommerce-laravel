<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCount extends Model
{
    protected $guarded = ['id', 'created_at'];
    protected $casts = ['quantity'=>'integer', 'count_round'=>'integer', 'counted_at'=>'datetime'];
    public function session() { return $this->belongsTo(InventorySession::class, 'inventory_session_id'); }
    public function location() { return $this->belongsTo(Location::class); }
    public function variant() { return $this->belongsTo(Variant::class)->withoutGlobalScope(\App\Models\Scopes\ActiveVariantScope::class); }
    public function user() { return $this->belongsTo(User::class); }
}
