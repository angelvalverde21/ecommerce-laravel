<?php

namespace App\Traits;

trait HasDateFiltersTrait
{
    public function scopeBetweenDates($query, $startDate, $endDate, $column = 'created_at')
    {
        if ($startDate && $endDate) {
            return $query->whereBetween($column, [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        return $query;
    }
}