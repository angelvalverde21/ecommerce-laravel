<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Yape extends Payment
{
    protected $table = 'payments';

    protected $attributes = [
        'method' => 'yape',
    ];
}