<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\NextTaskDto;
use App\Dto\TaskCompletionRequestDto;
use App\Dto\TaskCompletionResponseDto;
use App\Entity\Game;
use App\Entity\GameTask;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserGame;
use App\Entity\UserGameTask;
use App\Exception\InvalidTaskSequenceException;
use App\Exception\TaskAlreadyCompletedException;
use App\Exception\WrongLocationException;
use App\Repository\GameRepository;
use App\Repository\GameTaskRepository;
use App\Repository\UserGameRepository;
use App\Repository\UserGameTaskRepository;
use App\Service\Exception\GameAlreadyStartedException;
use App\Service\Exception\GameHasNoTasksException;
use App\Service\Exception\GameUnavailableException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmozart\Assert\Assert;

final class GamePlayService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameRepository $gameRepository,
        private readonly UserGameRepository $userGameRepository,
        private readonly GameTaskRepository $gameTaskRepository,
        private readonly UserGameTaskRepository $userGameTaskRepository,
        private readonly GeolocationService $geolocationService
    ) {
    }

    public function startGameForUser(Game $game, User $user): UserGame
    {
        if (!$game->isAvailable()) {
            throw new GameUnavailableException();
        }

        if (!$this->gameTaskRepository->findFirstTaskForGame($game)) {
            throw new GameHasNoTasksException();
        }

        if ($this->userGameRepository->findActiveGameForUser($user, $game)) {
            throw new GameAlreadyStartedException();
        }

        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);

        $this->entityManager->persist($userGame);
        $this->entityManager->flush();

        return $userGame;
    }

    public function completeTask(
        User $user,
        UserGame $userGame,
        Task $task,
        TaskCompletionRequestDto $requestDto
    ): TaskCompletionResponseDto {
        // 1. Basic checks
        if ($userGame->getCompletedAt() !== null) {
            throw new BadRequestHttpException('This game session is already completed.');
        }

        Assert::eq($userGame->getUser(), $user, 'User does not own this game session.');

        // 2. Find the GameTask linking the Game and the Task
        $gameTask = $this->gameTaskRepository->findOneBy(['game' => $userGame->getGame(), 'task' => $task]);
        if (!$gameTask) {
            throw new BadRequestHttpException('This task is not part of the current game.');
        }

        // 3. Check if this task is the next one in sequence
        $lastCompletedTask = $this->userGameTaskRepository->findLastCompletedTaskForUserGame($userGame);

        if ($lastCompletedTask === null) {
            // This is the first task to be completed. It must be the first in the game's sequence.
            $firstGameTask = $this->gameTaskRepository->findFirstTaskForGame($userGame->getGame());
            if ($gameTask !== $firstGameTask) {
                throw new InvalidTaskSequenceException('This is not the first task in the game.');
            }
        } else {
            // Find what the actual next task should be
            $nextTaskInSequence = $this->gameTaskRepository->findNextTaskInSequence(
                $userGame->getGame(),
                $lastCompletedTask->getGameTask()->getSequenceOrder()
            );

            if ($gameTask !== $nextTaskInSequence) {
                throw new InvalidTaskSequenceException();
            }
        }

        // 4. Check if task is already completed (redundant due to sequence check, but good for data integrity)
        $existingUserGameTask = $this->userGameTaskRepository->findOneBy(['userGame' => $userGame, 'gameTask' => $gameTask]);
        if ($existingUserGameTask) {
            throw new TaskAlreadyCompletedException();
        }

        // 5. Geolocation validation
        if (!$this->geolocationService->isWithinDistance(
            (float) $task->getLatitude(),
            (float) $task->getLongitude(),
            $requestDto->latitude,
            $requestDto->longitude,
            $task->getAllowedDistance()
        )) {
            throw new WrongLocationException();
        }

        // 6. Persist the completion
        $userGameTask = new UserGameTask();
        $userGameTask->setUserGame($userGame);
        $userGameTask->setGameTask($gameTask);

        $this->entityManager->persist($userGameTask);
        $this->entityManager->flush(); // Flush to make it available for the next queries

        // 7. Check if game is finished and determine next task
        $gameCompleted = $this->checkAndFinishGame($userGame);
        $nextTaskDto = null;

        if (!$gameCompleted) {
            $nextGameTask = $this->gameTaskRepository->findNextTaskInSequence($userGame->getGame(), $gameTask->getSequenceOrder());
            if ($nextGameTask) {
                $nextTaskDto = new NextTaskDto(
                    $nextGameTask->getTask()->getId(),
                    $nextGameTask->getTask()->getName(),
                    $nextGameTask->getTask()->getDescription(),
                    $nextGameTask->getSequenceOrder()
                );
            }
        }

        // 8. Return response DTO
        return new TaskCompletionResponseDto(true, $nextTaskDto, $gameCompleted);
    }

    /**
     * Checks if all tasks in a user game are completed and finishes the game if so.
     * Returns true if the game was just completed, false otherwise.
     */
    private function checkAndFinishGame(UserGame $userGame): bool
    {
        $totalGameTasks = $this->gameTaskRepository->count(['game' => $userGame->getGame()]);
        $completedUserGameTasks = $this->userGameTaskRepository->count(['userGame' => $userGame]);

        if ($totalGameTasks > 0 && $completedUserGameTasks >= $totalGameTasks) {
            if ($userGame->getCompletedAt() === null) {
                $userGame->setCompletedAt(new \DateTimeImmutable());
                $this->entityManager->flush();
                return true; // Game was just completed
            }
        }

        return false; // Game is not yet completed or was already completed
    }
}

