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

    protected $guarded = ['id', 'created_at'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function option_values()
    {
        return $this->hasMany(OptionValue::class);
    }
}
