<?php

declare(strict_types=1);

namespace App\Service;

final class EmailAlreadyExistsException extends \DomainException
{
    public static function create(string $email): self
    {
        return new self(sprintf('Użytkownik z adresem email "%s" już istnieje.', $email));
    }
}

