<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserGameRepository;
use App\Repository\UserGameTaskRepository;

class StatisticsQueryService
{
    public function __construct(
        private readonly UserGameRepository $userGameRepository,
        private readonly UserGameTaskRepository $userGameTaskRepository
    ) {
    }

    // Placeholder for methods to retrieve statistical data, e.g.,
    // public function getGameCompletionRanking(): array
    // {
    //     // Logic to fetch and format game completion times
    // }

    // public function getUserTaskCompletionStats(User $user): array
    // {
    //     // Logic to fetch and format user task completion statistics
    // }
}
