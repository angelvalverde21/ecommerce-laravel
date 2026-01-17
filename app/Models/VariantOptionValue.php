<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantOptionValue extends Model
{
    //
    protected $table = 'variant_option_values';

    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'option_id',
        'option_value_id',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function option()
    {
        return $this->belongsTo(Option::class);
    }

    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class);
    }
}
