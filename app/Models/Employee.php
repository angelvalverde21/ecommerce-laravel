<?php

namespace App\Models;

use App\Traits\HasDateFiltersTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Employee
 *
 * Documentación:
 * file://./readme.md
 */

class Employee extends Model
{
    //
    use HasDateFiltersTrait;

    protected $guarded = ['id', 'created_at'];

    protected $with = ['user'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class)->orderByDesc('created_at');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable')->orderBy('date', 'desc');
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->whereHas('user', function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('phone', 'LIKE', "%{$search}%")
                ->orWhere('document_number', 'LIKE', "%{$search}%");
        });
    }
}
