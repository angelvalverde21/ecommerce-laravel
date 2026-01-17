<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionValue extends Model
{
    //

    protected $table = 'option_values';

    protected $guarded = ['id', 'created_at'];

    public function option()
    {
        return $this->belongsTo(Option::class);
    }

    public function variants()
    {
        return $this->belongsToMany(
            Variant::class,
            'variant_option_values'
        );
    }
}
