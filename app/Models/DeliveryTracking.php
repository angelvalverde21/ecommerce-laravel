<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryTracking extends Model
{
    protected $guarded = ['id', 'created_at'];

    protected $hidden = ['trackable_type', 'trackable_id'];

    protected $appends = ['status', 'received_late'];

    protected $casts = [
        'handed_over_on' => 'date:Y-m-d', 'expected_on' => 'date:Y-m-d',
        'received_on' => 'date:Y-m-d', 'revision' => 'integer',
    ];

    public function trackable()
    {
        return $this->morphTo();
    }

    public function events()
    {
        return $this->hasMany(DeliveryTrackingEvent::class)->orderByDesc('id');
    }

    public function reschedules()
    {
        return $this->events()->where('type', 'rescheduled');
    }

    public function scheduleHistory()
    {
        return $this->hasMany(DeliveryTrackingEvent::class)
            ->whereIn('type', ['created', 'rescheduled'])->orderByDesc('id');
    }

    public function getStatusAttribute(): string
    {
        if ($this->received_on) {
            return 'received';
        }

        return $this->expected_on->format('Y-m-d') < now('America/Lima')->toDateString() ? 'overdue' : 'pending';
    }

    public function getReceivedLateAttribute(): bool
    {
        return $this->received_on && $this->received_on->gt($this->expected_on);
    }
}
