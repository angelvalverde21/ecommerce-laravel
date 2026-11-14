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

    protected $casts = ['show_in_stepper' => 'boolean', 'delivery_tracking_default' => 'boolean'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
