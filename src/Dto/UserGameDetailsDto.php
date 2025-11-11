<?php

declare(strict_types=1);

namespace App\Dto;

class UserGameDetailsDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $gameId,
        public readonly string $gameName,
        public readonly \DateTimeImmutable $startedAt,
        public readonly ?\DateTimeImmutable $completedAt
    ) {
    }
}
