<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    // Puede estar vacío, es totalmente válido
    public function __construct(
        string $message,
        public int $status = 422
    ) {
        parent::__construct($message);
    }
}
