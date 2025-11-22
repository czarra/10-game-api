<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CompletedGameListItemDto
{
    public function __construct(
        public string $userGameId,
        public string $gameId,
        public string $gameName,
        public string $startedAt,
        public string $completedAt,
        public int $completionTime, // in seconds
        public int $totalTasks
    ) {
    }
}
