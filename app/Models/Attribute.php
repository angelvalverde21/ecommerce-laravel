<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    /** @use HasFactory<\Database\Factories\AttributeFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    const DEFAULT_OPTIONS = [
        [
            'name' => 'brand',
            'label' => 'Marca', //Ejemplo Garmin
            'sort_order' => 1,
        ],
        [
            'name' => 'model',
            'label' => 'Modelo', //Ejemplo NUVI 1300
            'sort_order' => 2,
        ],
        [
            'name' => 'material',
            'label' => 'Material',
            'sort_order' => 3,
        ],
    ];

    public function attribute_values()
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->orderBy('sort_order', 'ASC');
    }

    public function attributeable()
    {
        return $this->morphTo();
    }
}
