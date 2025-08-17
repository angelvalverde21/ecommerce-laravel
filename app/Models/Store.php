<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
    
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
