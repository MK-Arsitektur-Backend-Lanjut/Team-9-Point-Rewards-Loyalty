<?php

namespace App\Exceptions;

use Exception;

class InvalidPointRuleException extends Exception
{
    public function __construct(string $message = "Invalid point rule")
    {
        parent::__construct($message);
    }
}