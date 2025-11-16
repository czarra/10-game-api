<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserGame;
use App\Entity\UserGameTask;
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
use Symfony\Component\Security\Core\User\UserInterface;

class GamePlayService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameRepository $gameRepository,
        private readonly UserGameRepository $userGameRepository,
        private readonly GameTaskRepository $gameTaskRepository,
        private readonly UserGameTaskRepository $userGameTaskRepository,
        private readonly GeolocationService $geolocationService // Assuming this service will be created later
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

    /**
     * Completes a task within an active user game.
     *
     * @param User $user The user completing the task.
     * @param string $userGameId The ID of the active user game.
     * @param string $taskId The ID of the task to complete.
     * @param float $latitude The current latitude of the user.
     * @param float $longitude The current longitude of the user.
     * @return UserGameTask The newly created UserGameTask entity.
     * @throws NotFoundHttpException If the user game or task is not found.
     * @throws BadRequestHttpException If the task is not part of the game or location is too far.
     */
    public function completeTask(User $user, string $userGameId, string $taskId, float $latitude, float $longitude): UserGameTask
    {
        $userGame = $this->userGameRepository->findOneBy(['id' => $userGameId, 'user' => $user, 'completedAt' => null]);

        if (!$userGame) {
            throw new NotFoundHttpException('Active game session not found.');
        }

        $game = $userGame->getGame();
        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task) {
            throw new NotFoundHttpException('Task not found.');
        }

        $gameTask = $this->gameTaskRepository->findOneBy(['game' => $game, 'task' => $task]);

        if (!$gameTask) {
            throw new BadRequestHttpException('This task is not part of the current game.');
        }

        // Check if task is already completed in this user game session
        $existingUserGameTask = $this->userGameTaskRepository->findOneBy(['userGame' => $userGame, 'gameTask' => $gameTask]);
        if ($existingUserGameTask) {
            throw new BadRequestHttpException('Task already completed in this session.');
        }

        // Geolocation validation (assuming GeolocationService is implemented)
        if (!$this->geolocationService->isWithinDistance(
            (float) $task->getLatitude(),
            (float) $task->getLongitude(),
            $latitude,
            $longitude,
            $task->getAllowedDistance()
        )) {
            throw new BadRequestHttpException('You are too far from the task location.');
        }

        $userGameTask = new UserGameTask();
        $userGameTask->setUserGame($userGame);
        $userGameTask->setGameTask($gameTask);

        $this->entityManager->persist($userGameTask);
        $this->entityManager->flush();

        // Optionally, check if all tasks are completed and finish the game
        $this->checkAndFinishGame($userGame);

        return $userGameTask;
    }

    /**
     * Checks if all tasks in a user game are completed and finishes the game if so.
     *
     * @param UserGame $userGame The user game to check.
     */
    private function checkAndFinishGame(UserGame $userGame): void
    {
        $game = $userGame->getGame();
        $totalGameTasks = $this->gameTaskRepository->count(['game' => $game]);
        $completedUserGameTasks = $this->userGameTaskRepository->count(['userGame' => $userGame]);

        if ($totalGameTasks > 0 && $completedUserGameTasks >= $totalGameTasks) {
            $userGame->setCompletedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }
    }
}
