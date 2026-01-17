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
        ],
        [
            'name' => 'brand',
            'label' => 'Marca',
            'sort_order' => 3,
        ],
        [
            'name' => 'model',
            'label' => 'Modelo',
            'sort_order' => 4,
        ],
        [
            'name' => 'material',
            'label' => 'Material',
            'sort_order' => 5,
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
}
