<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionStage extends Model
{
    public const DEFINITIONS = [
        ['molds', 'Moldes y ploteo'],
        ['samples', 'Muestras'],
        ['materials', 'Telas e insumos'],
        ['cutting', 'Corte'],
        ['sewing', 'Costura'],
        ['finishing', 'Acabados'],
    ];

    protected $guarded = ['id', 'created_at'];

    protected $casts = [
        'expected_end' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'sample_photos' => 'array',
        'sample_quantity' => 'integer',
        'revision' => 'integer',
    ];

    public function manufacture()
    {
        return $this->belongsTo(Manufacture::class);
    }

    public function events()
    {
        return $this->hasMany(ProductionStageEvent::class)->orderByDesc('id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
