<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kardex extends Model
{
    //
        protected $hidden = [
        'kardexable_type',
        'kardexable_id',
    ];
}
