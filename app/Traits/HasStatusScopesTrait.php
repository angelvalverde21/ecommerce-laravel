<?php

namespace App\Traits;

use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;

trait HasStatusScopesTrait
{
    /**
     * Scope: solo activos
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', Status::ACTIVE);
    }

    /**
     * Scope: solo bloqueados/inactivos
     */
    public function scopeBlocked(Builder $query)
    {
        return $query->where('status', Status::INACTIVE);
    }
}
