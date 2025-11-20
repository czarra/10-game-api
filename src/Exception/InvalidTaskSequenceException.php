<?php

declare(strict_types=1);

namespace App\Exception;

use DomainException;

final class InvalidTaskSequenceException extends DomainException
{
    public function __construct(string $message = 'This task is not the next in sequence to be completed.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
