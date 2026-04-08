<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    //

    protected $guarded = ['id', 'created_at'];

    public function batchable()
    {
        return $this->morphTo();
    }

    public function items(){
        return $this->hasMany(BatchItem::class);
    }

}
