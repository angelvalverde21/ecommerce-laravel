<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;
    
    protected $guarded = ['id', 'created_at'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function addressable()
    {
        return $this->morphTo();
    }
}
