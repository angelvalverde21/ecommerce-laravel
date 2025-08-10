<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    /** @use HasFactory<\Database\Factories\MaterialFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    
    CONST ELIMINADO = 0;
    CONST ACTIVO = 1;
    CONST ARCHIVADO = 2;
    CONST BORRADOR = 3;
    
    public function units(){
        return $this->hasMany(Unit::class);
    }

}
