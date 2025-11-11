<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Game;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class AtLeastThreeTasksValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AtLeastThreeTasks) {
            throw new UnexpectedTypeException($constraint, AtLeastThreeTasks::class);
        }

        if (!$value instanceof Game) {
            throw new UnexpectedValueException($value, Game::class);
        }

        // Only validate if the game is available
        if ($value->isAvailable()) {
            if ($value->getGameTasks()->count() < 3) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('isAvailable') // Attach the violation to the isAvailable field
                    ->addViolation();
            }
        }
    }
}
