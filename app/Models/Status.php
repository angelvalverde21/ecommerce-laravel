<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    //
    const ELIMINADO = 0;
    const ACTIVO = 1;
    const ARCHIVADO = 2;
    const BORRADOR = 3;
}
