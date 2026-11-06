<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{

    protected $guarded = ['id', 'created_at'];
    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    protected $appends = ['scope_type'];

    public function locationProgress() { return $this->hasMany(InventoryLocationProgress::class); }
    public function adjustments() { return $this->hasMany(InventoryAdjustment::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_user_id'); }
    public function counts() { return $this->hasManyThrough(InventoryCount::class, InventorySession::class, 'inventory_id', 'inventory_session_id'); }
    public function sessions() { return $this->hasMany(InventorySession::class); }
    public function inventoriable() { return $this->morphTo(); }
    public function getScopeTypeAttribute(): ?string {
        return match ($this->inventoriable_type) { (new Branch)->getMorphClass() => 'branch', (new Warehouse)->getMorphClass() => 'warehouse', (new Location)->getMorphClass() => 'location', default => null };
    }

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
