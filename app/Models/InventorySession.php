<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySession extends Model
{
    /** @use HasFactory<\Database\Factories\InventorySessionFactory> */
    use HasFactory;
    protected $fillable = ['inventory_id', 'warehouse_id', 'user_id', 'status', 'started_at', 'ended_at', 'comment'];
    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function counts() { return $this->hasMany(InventoryCount::class, 'inventory_session_id'); }
    public function inventory() { return $this->belongsTo(Inventory::class); }
    public function user() { return $this->belongsTo(User::class); }
}
