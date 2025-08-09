<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    CONST BORRADO = 0;
    CONST PUBLICADO = 1;
    CONST ARCHIVADO = 2;

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
