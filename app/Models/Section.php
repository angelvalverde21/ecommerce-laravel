<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    /** @use HasFactory<\Database\Factories\SectionFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    public function parent()
    {
        return $this->belongsTo(Section::class, 'parent_id');
    }

    public function childrens()
    {
        return $this->hasMany(Section::class, 'parent_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'section_id');
    }

    public function purchasesForBatch($batchId)
    {
        return $this->morphMany(Purchase::class, 'purchaseable')
            ->where('purchaseable_type', Batch::class)
            ->where('purchaseable_id', $batchId);
    }
}
