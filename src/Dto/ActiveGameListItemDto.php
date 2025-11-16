<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class ActiveGameListItemDto
{
    public function __construct(
        public string $userGameId,
        public string $gameId,
        public string $gameName,
        public string $description,
        public string $startedAt,
        public int $completedTasks,
        public int $totalTasks,
        public ?CurrentTaskDto $currentTask,
    ) {
    }
}
