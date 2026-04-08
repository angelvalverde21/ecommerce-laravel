<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchItem extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    protected $table = 'batch_items';
    

    public function variant(){
        return $this->belongsTo(Variant::class);
    }

}
