<?php

namespace App\Exceptions;

use RuntimeException;

class RepositoryException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
