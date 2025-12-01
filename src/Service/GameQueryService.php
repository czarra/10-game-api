<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ActiveGameListItemDto;
use App\Dto\CurrentTaskDto;
use App\Dto\GameDetailsDto;
use App\Dto\GameListItemDto;
use App\Dto\CompletedGameListItemDto;
use App\Dto\PaginatedResponseDto;
use App\Dto\PaginationDto;
use App\Dto\TaskDetailsDto;
use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Repository\UserGameRepository;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class GameQueryService
{
    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly UserGameRepository $userGameRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findAvailableGames(int $page, int $limit): PaginatedResponseDto
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

        return new PaginatedResponseDto(
            data: $dtos,
            pagination: new PaginationDto(
                page: $page,
                limit: $limit,
                total: $total,
                pages: $pages
            )
        );
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

    public function findActiveGameById(string $userGameId, UserInterface $user): ActiveGameListItemDto
    {
        $gameData = $this->userGameRepository->findActiveGameDetails($userGameId, $user);

        if (null === $gameData) {
            throw new NotFoundHttpException('Active game not found.');
        }
        $currentTask = null;

        if (isset($gameData['currentTask'])) {

            $taskData = $gameData['currentTask'];

            $currentTask = new CurrentTaskDto(
                id: $taskData['id']->toRfc4122(),
                name: $taskData['name'],
                description: $taskData['description'],
                sequenceOrder: $taskData['sequenceOrder'],
            );
        }

        return new ActiveGameListItemDto(
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

    public function findCompletedForUser(UserInterface $user, int $page, int $limit): PaginatedResponseDto
    {
        if (!$user instanceof User) {
            // This should not happen with a properly configured security setup
            $this->logger->error('User is not an instance of App\Entity\User', ['userId' => $user->getUserIdentifier()]);
            // Depending on the desired behavior, you might throw an exception or return an empty result.
            // Returning an empty result is safer to prevent information leaks.
            return new PaginatedResponseDto(
                data: [],
                pagination: new PaginationDto(
                    page: $page,
                    limit: $limit,
                    total: 0,
                    pages: 0
                )
            );
        }

        $paginator = $this->userGameRepository->findCompletedByUserPaginated($user, $page, $limit);

        $total = count($paginator);
        $pages = (int) ceil($total / $limit);

        $dtos = [];
        foreach ($paginator as $result) {
            $userGame = $result[0];
            $completionTime = (int) $result['completionTime'];
            $totalTasks = (int) $result['totalTasks'];

            $dtos[] = new CompletedGameListItemDto(
                userGameId: $userGame->getId()->toRfc4122(),
                gameId: $userGame->getGame()->getId()->toRfc4122(),
                gameName: $userGame->getGame()->getName(),
                startedAt: $userGame->getStartedAt()->format(\DateTime::ATOM),
                completedAt: $userGame->getCompletedAt()->format(\DateTime::ATOM),
                completionTime: $completionTime,
                totalTasks: $totalTasks
            );
        }

        return new PaginatedResponseDto(
            data: $dtos,
            pagination: new PaginationDto(
                page: $page,
                limit: $limit,
                total: $total,
                pages: $pages
            )
        );
    }
}