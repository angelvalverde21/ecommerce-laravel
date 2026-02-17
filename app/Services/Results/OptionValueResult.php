<?php

namespace App\Services\Results;

use App\Models\OptionValue;

class OptionValueResult
{
    public function __construct(
        public OptionValue $data,
        public bool $created
    ) {}
}