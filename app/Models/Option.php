<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    /** @use HasFactory<\Database\Factories\OptionFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    protected $fillable = ['name'];

    public function values()
    {
        return $this->hasMany(OptionValue::class);
    }
}
