<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
