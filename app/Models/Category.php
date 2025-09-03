<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

        protected $guarded = ['id'];

    // protected $appends = [
    //     'profile_photo_url',
    // ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class); //en la tabla Category busca el atributo 'seller_id' y le hace un where a la tabla Users
    }

    public function images() //para llamar a esta funcion se usa $this->images o $this->images()->get() pero aqui se usa get() para traer la coleccion
    {
        return $this->morphMany(Image::class, "imageable")->orderBy('id', 'DESC');
    }
}
