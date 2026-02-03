<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManufactureVariant extends Model
{
    //
        protected $guarded = ['id', 'created_at'];

    protected $table = 'manufacture_variant';


    public function variant(){
        return $this->belongsTo(Variant::class);
    }
}
