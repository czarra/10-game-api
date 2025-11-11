<?php

declare(strict_types=1);

namespace App\Dto;

final class TaskDetailsDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly ?int $sequenceOrder = null
    ) {}
}
