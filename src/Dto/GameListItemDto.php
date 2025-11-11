<?php

declare(strict_types=1);

namespace App\Dto;

final class GameListItemDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly bool $isAvailable
    ) {
    }
}
