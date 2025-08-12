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

    public function childrenSectionsPurchases()
    {
        // Obtener los ids de los childrens en tiempo de ejecución (NO funciona con eager loading directo)
        $childrenIds = $this->section->childrens->pluck('id');

        return Purchase::whereIn('section_id', $childrenIds);
    }
}
