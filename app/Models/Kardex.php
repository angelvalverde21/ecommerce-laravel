<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kardex extends Model
{
    //

    protected $guarded = ['id', 'created_at'];

    protected $hidden = [
        'kardexable_type',
        'kardexable_id',
    ];


    public function kardexable()
    {
        return $this->morphTo();
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
