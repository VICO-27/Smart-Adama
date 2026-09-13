<?php

namespace App\Exceptions;

use Exception;

class RAGRetrievalException extends Exception
{
    public function __construct(string $message, public readonly string $stage = 'vector_retrieval', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
