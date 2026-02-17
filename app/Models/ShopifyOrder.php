<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyOrder extends Model
{
    //

    protected $table = 'shopify_orders';

    protected $guarded = ['id', 'created_at'];

    public function syncs()
    {
        return $this->morphMany(Sync::class, 'syncable');
    }
    
}
