<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManufactureProduct extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    protected $table = 'manufacture_product';

}
