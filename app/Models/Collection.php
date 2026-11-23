<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $guarded = ['id'];
    public function processes() { return $this->hasMany(ProjectProcess::class, 'collection_id'); }
}
