<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    //

    const TOLERANCE = 15;

    protected $guarded = ['id', 'created_at'];

    public function employee(){
        return $this->belongsTo(Employee::class);
    }

}
