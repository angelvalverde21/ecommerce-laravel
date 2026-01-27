<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    //
    const INACTIVE = 0;
    const ACTIVE = 1;
    const DRAFT = 2;
    const ARCHIVED = 3;
    const TRASH = 4;
    //Product::TRASH = 0, Product::ACTIVE = 1, Product::DRAFT = 2, Product::ARCHIVED = 3
}
