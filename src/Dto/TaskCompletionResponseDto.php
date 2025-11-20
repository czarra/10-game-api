<?php

declare(strict_types=1);

namespace App\Dto;

final class TaskCompletionResponseDto
{
    public function __construct(
        public readonly bool $completed,
        public readonly ?NextTaskDto $nextTask,
        public readonly bool $gameCompleted,
    ) {
    }
}
