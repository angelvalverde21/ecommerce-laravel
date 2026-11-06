<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'address_id', 'name', 'code', 'is_main', 'is_active'];
    protected $casts = ['is_main' => 'boolean', 'is_active' => 'boolean'];

    public function store() { return $this->belongsTo(Store::class); }
    public function address() { return $this->belongsTo(Address::class); }
    public function warehouses() { return $this->hasMany(Warehouse::class); }
}
