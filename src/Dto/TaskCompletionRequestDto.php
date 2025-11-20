<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class TaskCompletionRequestDto
{
    public function __construct(
        #[Assert\NotNull(message: 'Latitude cannot be null.')]
        #[Assert\Type(type: 'float', message: 'Latitude must be a float.')]
        public readonly float $latitude,

        #[Assert\NotNull(message: 'Longitude cannot be null.')]
        #[Assert\Type(type: 'float', message: 'Longitude must be a float.')]
        public readonly float $longitude,
    ) {
    }
}
