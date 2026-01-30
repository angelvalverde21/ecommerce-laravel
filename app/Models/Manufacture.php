<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacture extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchaseable');
    }

}
