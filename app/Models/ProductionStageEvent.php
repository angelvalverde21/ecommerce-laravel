<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionStageEvent extends Model
{
    protected $guarded = ['id', 'created_at'];

    protected $casts = ['changes' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
