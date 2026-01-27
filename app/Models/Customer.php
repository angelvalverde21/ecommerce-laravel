<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Customer
 *
 * Documentación:
 * file://./readme.md
 */

class Customer extends Model
{
    //

    protected $guarded = ['id', 'created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
