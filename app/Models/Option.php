<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    //

    const DEFAULT_OPTIONS = [
        [
            'name' => 'color',
            'label' => 'Colores',
            'sort_order' => 1,
        ],
        [
            'name' => 'size',
            'label' => 'Tallas',
            'sort_order' => 2,
        ]

    ];

    const DEFAULT = [
        'color' => [
            'label' => 'Colores',
            'sort_order' => 1,
        ],
        'size' => [
            'label' => 'Tallas',
            'sort_order' => 2,
        ],
    ];

    protected $guarded = ['id', 'created_at'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function option_values()
    {
        return $this->hasMany(OptionValue::class);
    }

    public function optionValues() //Relación con camelCase para seguir convenciones de Laravel, se dejara por mientras mientras cambiamos todas las relaciones a camelCase
    {
        return $this->hasMany(OptionValue::class);
    }
}
