<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyProduct extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    protected $table = 'shopify_products';

    public function variants()
    {
        return $this->hasMany(ShopifyVariant::class, 'shopify_product_id');
    }

    public function syncs()
    {
        return $this->morphMany(Sync::class, 'syncable');
    }
}
