<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportShopifyOrderUpdate extends Model
{
    protected $table = 'import_shopify_order_updates';
    protected $guarded = ['id'];
    protected $casts = ['search_from' => 'immutable_datetime', 'search_until' => 'immutable_datetime',
        'scan_completed_at' => 'datetime', 'completed_at' => 'datetime', 'checked_orders' => 'integer', 'added_orders' => 'integer', 'task_baseline' => 'integer'];

    public function run() { return $this->belongsTo(ImportShopifyRun::class, 'run_id'); }
}
