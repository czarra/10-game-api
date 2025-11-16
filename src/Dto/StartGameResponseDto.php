<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class StartGameResponseDto
{
    public function __construct(
        public string $userGameId,
        public string $gameId,
        public string $startedAt,
        public CurrentTaskDto $currentTask,
    ) {}
}
