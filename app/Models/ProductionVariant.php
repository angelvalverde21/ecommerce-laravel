<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionVariant extends Model
{
    //
    //
    protected $guarded = ['id', 'created_at'];

    protected $table = 'production_variant';


    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
