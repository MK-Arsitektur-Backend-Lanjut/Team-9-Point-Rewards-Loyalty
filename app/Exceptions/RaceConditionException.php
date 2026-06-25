<?php

namespace App\Exceptions;

use Exception;

class RaceConditionException extends Exception
{
    public function __construct(string $message = "Failed to process points due to concurrent operations")
    {
        parent::__construct($message);
    }
}