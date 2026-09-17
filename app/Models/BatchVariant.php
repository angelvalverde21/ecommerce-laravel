<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchVariant extends Model
{
    /** @use HasFactory<\Database\Factories\BatchVariantFactory> */
    use HasFactory;


    protected $table = 'batch_variant';

    protected $guarded = ['id', 'created_at'];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

}
