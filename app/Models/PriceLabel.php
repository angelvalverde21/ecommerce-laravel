<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PriceLabel extends Model
{
    protected $fillable = ['name', 'code'];

    public static function forName(string $name): self
    {
        $name = Str::squish($name);
        $code = Str::slug($name, '_');
        if ($code === '') {
            throw ValidationException::withMessages(['name' => 'El nombre del precio debe contener letras o números.']);
        }

        // Reuse the canonical label for case, accent and separator variations.
        return static::firstOrCreate(['code' => $code], ['name' => $name]);
    }
}
