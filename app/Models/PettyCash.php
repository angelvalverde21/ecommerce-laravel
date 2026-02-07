<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    //
    public function payments(){
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

     public function store(){
        return $this->belongsTo(Store::class);
    }
}
