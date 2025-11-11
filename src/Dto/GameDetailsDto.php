<?php

declare(strict_types=1);

namespace App\Dto;

final class GameDetailsDto
{
    /**
     * @param string $id
     * @param string $name
     * @param string $description
     * @param TaskDetailsDto[] $tasks
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly array $tasks
    ) {}
}
