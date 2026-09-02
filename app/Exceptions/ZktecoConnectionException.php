<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ZktecoConnectionException extends RuntimeException
{
    public function __construct(string $message, public readonly string $deviceIp, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
