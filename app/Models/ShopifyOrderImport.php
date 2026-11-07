<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyOrderImport extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['active_store_id'];

    protected $casts = [
        'completed_at' => 'datetime',
        'expected_orders' => 'integer',
        'expected_rows' => 'integer',
    ];
}
