<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GameDetailsDto;
use App\Dto\GameListItemDto;
use App\Dto\TaskDetailsDto;
use App\Repository\GameRepository;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;

final class GameQueryService
{
    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly LoggerInterface $logger
    ) {}

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
     * Retrieves a list of all available games.
     *
     * @return GameListItemDto[] An array of GameListItemDto objects.
     */
    public function getAvailableGames(): array
    {
        $games = $this->gameRepository->findBy(['isAvailable' => true]);
        $gameListItems = [];

        foreach ($games as $game) {
            $gameListItems[] = new GameListItemDto(
                $game->getId()->toRfc4122(),
                $game->getName(),
                $game->getDescription(),
                $game->isAvailable()
            );
        }

        return $gameListItems;
    }
}
