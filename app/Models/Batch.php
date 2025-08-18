<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    /** @use HasFactory<\Database\Factories\BatchFactory> */

    protected $guarded = ['id', 'created_at'];
    use HasFactory;

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchaseable');
    }

    // images() → morphMany para todas las imágenes.

    // image() → morphOne usando latestOfMany() si quieres la última imagen.
    // latestOfMany() es una función de Laravel pensada para esto: obtener la 
    // última relación en un morphOne o hasOne basado en un campo (por defecto created_at).

    public function images(){
        return $this->morphMany(Image::class, 'imageable')->orderBy('id', 'DESC');
    }

    public function image(){
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    // public function sections()
    // {
    //     return $this->morphMany(Image::class, 'sectionable')->orderBy('id', 'DESC');;
    // }


    // public function groups(){
    //     return $this->morphMany(Group::class, 'groupable');
    // }

}
