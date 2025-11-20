<?php

declare(strict_types=1);

namespace App\Exception;

use DomainException;

final class TaskAlreadyCompletedException extends DomainException
{
    public function __construct(string $message = 'This task has already been completed.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
