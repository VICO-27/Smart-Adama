<?php

namespace App\Exceptions;

use Exception;

class AiProviderException extends Exception
{
    public function __construct(string $message, public readonly string $provider)
    {
        parent::__construct($message);
    }
}
