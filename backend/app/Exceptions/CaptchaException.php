<?php

namespace App\Exceptions;

use RuntimeException;

class CaptchaException extends RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode = 500)
    {
        parent::__construct($message, $statusCode);
    }
}
