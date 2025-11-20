<?php

declare(strict_types=1);

namespace App\Exception;

use DomainException;

final class WrongLocationException extends DomainException
{
    public function __construct(string $message = 'User is not at the correct location for the task.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
