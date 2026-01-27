<?php

namespace App\Models;

use App\Traits\HasStatusScopesTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Courier
 *
 * Documentación:
 * file://./readme.md
 */

class Courier extends Model
{
    //

    protected $guarded = ['id', 'created_at'];

    protected $with = ['user'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
