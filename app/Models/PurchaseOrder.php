<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    //
    protected $guarded = ['id', 'created_at'];


    public function images()
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('id', 'DESC');;
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier()
    {
        return $this->belongsTo(User::class); //la tabla de supplier sera la de user con rol supplier
    }


}
