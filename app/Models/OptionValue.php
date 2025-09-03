<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionValue extends Model
{
    /** @use HasFactory<\Database\Factories\OptionValueFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    protected $table = 'option_value';

    // protected $fillable = ['option_id', 'value'];

    public function option()
    {
        return $this->belongsTo(Option::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_options')
            ->withPivot('stock', 'price')
            ->withTimestamps();
    }
}
