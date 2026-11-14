<?php

namespace App\Traits;

use App\Models\DeliveryTracking;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasDeliveryTracking
{
    public function deliveryTracking(): MorphOne
    {
        return $this->morphOne(DeliveryTracking::class, 'trackable')->withCount('reschedules');
    }
}
