<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    protected $casts = ['is_active' => 'boolean'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }

    public function directChildren()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->directChildren()->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }
}
