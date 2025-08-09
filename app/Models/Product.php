<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    CONST ELIMINADO = 0;
    CONST PUBLICADO = 1;
    CONST ARCHIVADO = 2;
    CONST BORRADOR = 3;
    
    public function units(){
        return $this->hasMany(Unit::class);
    }
}
