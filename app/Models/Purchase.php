<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    public function purchaseable()
    {
        return $this->morphTo();
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }


    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('id', 'DESC');;
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

}
