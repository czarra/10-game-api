<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Uid\Uuid;

class UuidValidator
{
    public function validate(string $uuid): bool
    {
        try {
            Uuid::fromString($uuid);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}
