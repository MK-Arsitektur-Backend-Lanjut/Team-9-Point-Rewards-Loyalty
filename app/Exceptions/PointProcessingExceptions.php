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

class RaceConditionException extends Exception
{
    public function __construct(string $message = "Failed to process points due to concurrent operations")
    {
        parent::__construct($message);
    }
}

class InvalidPointRuleException extends Exception
{
    public function __construct(string $message = "Invalid point rule")
    {
        parent::__construct($message);
    }
}
