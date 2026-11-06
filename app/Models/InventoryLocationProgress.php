<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLocationProgress extends Model
{
    protected $table = 'inventory_location_progress';
    protected $guarded = ['id', 'created_at'];
    protected $casts = ['started_at'=>'datetime', 'counted_at'=>'datetime', 'verified_at'=>'datetime'];
    public function inventory() { return $this->belongsTo(Inventory::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
}
