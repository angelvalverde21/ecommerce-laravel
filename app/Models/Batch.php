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

}
