<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

}
