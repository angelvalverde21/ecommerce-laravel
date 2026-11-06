<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'branch_id', 'name', 'subtitle', 'code', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    protected $appends = ['address', 'address_id'];

    public function getAddressAttribute($value = null): ?string
    {
        return $this->branch?->address?->primary;
    }

    public function getAddressIdAttribute(): ?int
    {
        return $this->branch?->address_id;
    }

    public function store() { return $this->belongsTo(Store::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function locations() { return $this->hasMany(Location::class); }
}
