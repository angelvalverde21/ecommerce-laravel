<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    //

    protected $guarded = ['id', 'created_at'];

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }
}
