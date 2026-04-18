<?php

namespace App\Models;

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
        return $this->morphMany(Payment::class, 'paymentable');
    }
}
