<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AtLeastThreeTasks extends Constraint
{
    public string $message = 'A game must have at least 3 tasks to be available.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
