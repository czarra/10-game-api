<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Uid\Uuid;

final class GameListItemDto
{
    public function __construct(
        public readonly Uuid $id,
        public readonly string $name,
        public readonly string $description,
        public readonly int $tasksCount,
    ) {
    }
}