<?php

namespace App\Exceptions;

use Exception;

class InsufficientPointsException extends Exception
{
    public function __construct(string $message = "Insufficient points balance")
    {
        parent::__construct($message);
    }
}