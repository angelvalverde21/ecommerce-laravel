<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batche extends Model //Batche significa lotes y se usara para lotes de producccion
{
    /** @use HasFactory<\Database\Factories\BatcheFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];
}
