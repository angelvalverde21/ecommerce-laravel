<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acquire extends Model
{
    //

    protected $guarded = ['id', 'created_at'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batches()
    {
        return $this->morphMany(Batch::class, 'batchable');
    }
}
