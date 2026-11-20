<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportShopifyRun extends Model
{
    protected $table = 'import_shopify_runs';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['active_store_id'];
    protected $casts = ['access_verified' => 'boolean', 'throttle' => 'array', 'heartbeat_at' => 'datetime', 'completed_at' => 'datetime'];
}
