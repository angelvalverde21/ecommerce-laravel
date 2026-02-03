<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    protected $hidden = [
        'purchaseable_type',
        'purchaseable_id',
    ];

    protected $casts = [
        'purchase_start' => 'date:Y-m-d',
        'purchase_end' => 'date:Y-m-d',
    ];

    public function purchaseable()
    {
        return $this->morphTo();
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
}
