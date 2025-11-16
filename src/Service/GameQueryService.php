<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ActiveGameListItemDto;
use App\Dto\CurrentTaskDto;
use App\Dto\GameDetailsDto;
use App\Dto\GameListItemDto;
use App\Dto\TaskDetailsDto;
use App\Entity\Game;
use App\Repository\GameRepository;
use App\Repository\UserGameRepository;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class GameQueryService
{
    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly UserGameRepository $userGameRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findAvailableGames(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $paginator = $this->gameRepository->createAvailableGamesPaginator($offset, $limit);
        $total = count($paginator);
        $pages = (int) ceil($total / $limit);

        $dtos = [];
        foreach ($paginator as $result) {
            /** @var Game $game */
            $game = $result[0];
            $tasksCount = (int) $result['tasksCount'];

            $dtos[] = new GameListItemDto(
                $game->getId(),
                $game->getName(),
                $game->getDescription(),
                $tasksCount,
            );
        }

        return [
            'data' => $dtos,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages,
            ],
        ];
    }

    public function getGameDetails(string $gameId): ?GameDetailsDto
    {
        try {
            $game = $this->gameRepository->findAvailableGame($gameId);

            if (!$game) {
                return null;
            }

            $tasks = [];
            foreach ($game->getGameTasks() as $gameTask) {
                $task = $gameTask->getTask();
                if ($task) {
                    $tasks[] = new TaskDetailsDto(
                        $task->getId()->toRfc4122(),
                        $task->getName(),
                        $task->getDescription(),
                        $gameTask->getSequenceOrder()
                    );
                }
            }

            return new GameDetailsDto(
                $game->getId()->toRfc4122(),
                $game->getName(),
                $game->getDescription(),
                $tasks
            );
        } catch (ORMException $e) {
            $this->logger->error('Database error fetching game', ['gameId' => $gameId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @return ActiveGameListItemDto[]
     */
    public function findActiveGamesForUser(UserInterface $user): array
    {
        $gamesData = $this->userGameRepository->findActiveGamesDetailsByUser($user);

        $result = [];
        foreach ($gamesData as $gameData) {
            $currentTask = null;
            if ($gameData['currentTask'] !== null) {
                $currentTask = new CurrentTaskDto(
                    id: $gameData['currentTask']['id']->toRfc4122(),
                    name: $gameData['currentTask']['name'],
                    description: $gameData['currentTask']['description'],
                    sequenceOrder: (int) $gameData['currentTask']['sequenceOrder']
                );
            }

            $result[] = new ActiveGameListItemDto(
                userGameId: $gameData['userGameId']->toRfc4122(),
                gameId: $gameData['gameId']->toRfc4122(),
                gameName: $gameData['gameName'],
                description: $gameData['gameDescription'],
                startedAt: $gameData['startedAt'],
                completedTasks: (int) $gameData['completedTasks'],
                totalTasks: (int) $gameData['totalTasks'],
                currentTask: $currentTask
            );
        }

        return $result;
    }
}