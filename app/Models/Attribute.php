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
            'name' => 'Marca',
            'sort_order' => 1,
        ],
        [
            'name' => 'Modelo',
            'sort_order' => 2,
        ],
                    [
            'name' => 'Material',
            'sort_order' => 3,
        ],
    ];

    public function attribute_values()
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->orderBy('sort_order', 'ASC');
    }


}
