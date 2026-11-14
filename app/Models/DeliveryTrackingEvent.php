<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryTrackingEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['changes' => 'array'];

    public function tracking()
    {
        return $this->belongsTo(DeliveryTracking::class, 'delivery_tracking_id');
    }
}
