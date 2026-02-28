<?php

namespace App\Traits;

use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;

trait HasStatusScopesTrait
{
    public function scopeActive(Builder $query)
    {
        // Si el modelo tiene relación 'user', filtra por la tabla users
        if (method_exists($query->getModel(), 'user')) {
            return $query->whereHas('user', function ($q) {
                $q->where('status', Status::ACTIVE);
            });
        }

        // Si el modelo tiene columna status directamente
        return $query->where('status', Status::ACTIVE);
    }

    public function scopeBlocked(Builder $query)
    {
        if (method_exists($query->getModel(), 'user')) {
            return $query->whereHas('user', function ($q) {
                $q->where('status', Status::INACTIVE);
            });
        }

        return $query->where('status', Status::INACTIVE);
    }
}