<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    //

    const TOLERANCE = 15;

    protected $guarded = ['id', 'created_at'];
    protected $casts = ['break_skipped' => 'boolean', 'absence_justified' => 'boolean', 'justified_at' => 'datetime'];
    protected $with = ['images'];

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
