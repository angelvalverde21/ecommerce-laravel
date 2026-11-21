<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyProductExport extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['variant_links' => 'array', 'attempted_at' => 'datetime', 'synced_at' => 'datetime'];
}
