<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\TaskCompletionRequestDto;
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
use App\Service\GamePlayService;
use App\Service\GeolocationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class GamePlayServiceTest extends TestCase
{
    private GamePlayService $gamePlayService;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|UserGameRepository $userGameRepository;
    private MockObject|GameTaskRepository $gameTaskRepository;
    private MockObject|UserGameTaskRepository $userGameTaskRepository;
    private MockObject|GeolocationService $geolocationService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userGameRepository = $this->createMock(UserGameRepository::class);
        $this->gameTaskRepository = $this->createMock(GameTaskRepository::class);
        $this->userGameTaskRepository = $this->createMock(UserGameTaskRepository::class);
        $this->geolocationService = $this->createMock(GeolocationService::class);

        $this->gamePlayService = new GamePlayService(
            $this->entityManager,
            $this->userGameRepository,
            $this->gameTaskRepository,
            $this->userGameTaskRepository,
            $this->geolocationService
        );
    }

    public function testStartGameForUserSuccess(): void
    {
        $game = new Game();
        $game->setIsAvailable(true);
        $user = new User();
        $firstTask = new GameTask();

        $this->gameTaskRepository->expects($this->once())
            ->method('findFirstTaskForGame')
            ->with($game)
            ->willReturn($firstTask);

        $this->userGameRepository->expects($this->once())
            ->method('findActiveGameForUser')
            ->with($user, $game)
            ->willReturn(null);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $userGame = $this->gamePlayService->startGameForUser($game, $user);

        $this->assertInstanceOf(UserGame::class, $userGame);
        $this->assertSame($user, $userGame->getUser());
        $this->assertSame($game, $userGame->getGame());
    }

    public function testStartGameForUserThrowsGameUnavailableException(): void
    {
        $this->expectException(GameUnavailableException::class);

        $game = new Game();
        $game->setIsAvailable(false);
        $user = new User();

        $this->gamePlayService->startGameForUser($game, $user);
    }

    public function testStartGameForUserThrowsGameHasNoTasksException(): void
    {
        $this->expectException(GameHasNoTasksException::class);

        $game = new Game();
        $game->setIsAvailable(true);
        $user = new User();

        $this->gameTaskRepository->expects($this->once())
            ->method('findFirstTaskForGame')
            ->with($game)
            ->willReturn(null);

        $this->gamePlayService->startGameForUser($game, $user);
    }

    public function testStartGameForUserThrowsGameAlreadyStartedException(): void
    {
        $this->expectException(GameAlreadyStartedException::class);

        $game = new Game();
        $game->setIsAvailable(true);
        $user = new User();
        $firstTask = new GameTask();
        $existingUserGame = new UserGame();

        $this->gameTaskRepository->expects($this->once())
            ->method('findFirstTaskForGame')
            ->with($game)
            ->willReturn($firstTask);

        $this->userGameRepository->expects($this->once())
            ->method('findActiveGameForUser')
            ->with($user, $game)
            ->willReturn($existingUserGame);

        $this->gamePlayService->startGameForUser($game, $user);
    }

    public function testCompleteTaskSuccess(): void
    {
        $user = new User();
        $game = new Game();
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);

        $task = new Task();
        $task->setLatitude('10.0');
        $task->setLongitude('20.0');
        $task->setAllowedDistance(100);

        $gameTask = new GameTask();
        $gameTask->setGame($game);
        $gameTask->setTask($task);
        $gameTask->setSequenceOrder(1);

        $requestDto = new TaskCompletionRequestDto(10.0, 20.0);

        $this->gameTaskRepository->method('findOneBy')->willReturn($gameTask);
        $this->userGameTaskRepository->method('findLastCompletedTaskForUserGame')->willReturn(null);
        $this->gameTaskRepository->method('findFirstTaskForGame')->willReturn($gameTask);
        $this->geolocationService->method('isWithinDistance')->willReturn(true);
        $this->gameTaskRepository->method('count')->willReturn(1);
        $this->userGameTaskRepository->method('count')->willReturn(1);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->exactly(2))->method('flush');

        $response = $this->gamePlayService->completeTask($user, $userGame, $task, $requestDto);

        $this->assertTrue($response->completed);
        $this->assertTrue($response->gameCompleted);
        $this->assertNull($response->nextTask);
    }

    public function testCompleteTaskThrowsGameAlreadyCompletedException(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('This game session is already completed.');

        $user = new User();
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setCompletedAt(new \DateTimeImmutable());
        $task = new Task();
        $requestDto = new TaskCompletionRequestDto(10.0, 20.0);

        $this->gamePlayService->completeTask($user, $userGame, $task, $requestDto);
    }

    public function testCompleteTaskThrowsUserMismatchException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User does not own this game session.');

        $user1 = (new User())->setEmail('user1@example.com');
        $user2 = (new User())->setEmail('user2@example.com');
        $userGame = new UserGame();
        $userGame->setUser($user1);
        $task = new Task();
        $requestDto = new TaskCompletionRequestDto(10.0, 20.0);

        $this->gamePlayService->completeTask($user2, $userGame, $task, $requestDto);
    }

    public function testCompleteTaskThrowsTaskNotInGameException(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('This task is not part of the current game.');

        $user = new User();
        $game = new Game();
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);
        $task = new Task();
        $requestDto = new TaskCompletionRequestDto(10.0, 20.0);

        $this->gameTaskRepository->method('findOneBy')->willReturn(null);

        $this->gamePlayService->completeTask($user, $userGame, $task, $requestDto);
    }

    public function testCompleteTaskThrowsInvalidTaskSequenceExceptionOnFirstTask(): void
    {
        $this->expectException(InvalidTaskSequenceException::class);
        $this->expectExceptionMessage('This is not the first task in the game.');

        $user = new User();
        $game = new Game();
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);

        $task1 = new Task();
        $gameTask1 = new GameTask();
        $gameTask1->setGame($game);
        $gameTask1->setTask($task1);
        $gameTask1->setSequenceOrder(1);

        $task2 = new Task();
        $gameTask2 = new GameTask();
        $gameTask2->setGame($game);
        $gameTask2->setTask($task2);
        $gameTask2->setSequenceOrder(2);

        $requestDto = new TaskCompletionRequestDto(10.0, 20.0);

        $this->gameTaskRepository->method('findOneBy')->willReturn($gameTask2);
        $this->userGameTaskRepository->method('findLastCompletedTaskForUserGame')->willReturn(null);
        $this->gameTaskRepository->method('findFirstTaskForGame')->willReturn($gameTask1);

        $this->gamePlayService->completeTask($user, $userGame, $task2, $requestDto);
    }

    public function testCompleteTaskThrowsInvalidTaskSequenceExceptionOnNextTask(): void
    {
        $this->expectException(InvalidTaskSequenceException::class);

        $user = new User();
        $game = new Game();
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);

        $task1 = new Task();
        $gameTask1 = new GameTask();
        $gameTask1->setGame($game);
        $gameTask1->setTask($task1);
        $gameTask1->setSequenceOrder(1);

        $userGameTask1 = new UserGameTask();
        $userGameTask1->setUserGame($userGame);
        $userGameTask1->setGameTask($gameTask1);

        $task2 = new Task();
        $gameTask2 = new GameTask();
        $gameTask2->setGame($game);
        $gameTask2->setTask($task2);
        $gameTask2->setSequenceOrder(2);

        $task3 = new Task();
        $gameTask3 = new GameTask();
        $gameTask3->setGame($game);
        $gameTask3->setTask($task3);
        $gameTask3->setSequenceOrder(3);

        $requestDto = new TaskCompletionRequestDto(10.0, 20.0);

        $this->gameTaskRepository->method('findOneBy')->willReturn($gameTask3);
        $this->userGameTaskRepository->method('findLastCompletedTaskForUserGame')->willReturn($userGameTask1);
        $this->gameTaskRepository->method('findNextTaskInSequence')->willReturn($gameTask2);

        $this->gamePlayService->completeTask($user, $userGame, $task3, $requestDto);
    }

    public function testCompleteTaskThrowsTaskAlreadyCompletedException(): void
    {
        $this->expectException(TaskAlreadyCompletedException::class);

        $user = new User();
        $game = new Game();
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);

        $task = new Task();
        $gameTask = new GameTask();
        $gameTask->setGame($game);
        $gameTask->setTask($task);
        $gameTask->setSequenceOrder(1);

        $requestDto = new TaskCompletionRequestDto(10.0, 20.0);

        $this->gameTaskRepository->method('findOneBy')->willReturn($gameTask);
        $this->userGameTaskRepository->method('findLastCompletedTaskForUserGame')->willReturn(null);
        $this->gameTaskRepository->method('findFirstTaskForGame')->willReturn($gameTask);
        $this->userGameTaskRepository->method('findOneBy')->willReturn(new UserGameTask());

        $this->gamePlayService->completeTask($user, $userGame, $task, $requestDto);
    }

    public function testCompleteTaskThrowsWrongLocationException(): void
    {
        $this->expectException(WrongLocationException::class);

        $user = new User();
        $game = new Game();
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);

        $task = new Task();
        $task->setLatitude('10.0');
        $task->setLongitude('20.0');
        $task->setAllowedDistance(100);

        $gameTask = new GameTask();
        $gameTask->setGame($game);
        $gameTask->setTask($task);
        $gameTask->setSequenceOrder(1);

        $requestDto = new TaskCompletionRequestDto(30.0, 40.0);

        $this->gameTaskRepository->method('findOneBy')->willReturn($gameTask);
        $this->userGameTaskRepository->method('findLastCompletedTaskForUserGame')->willReturn(null);
        $this->gameTaskRepository->method('findFirstTaskForGame')->willReturn($gameTask);
        $this->geolocationService->method('isWithinDistance')->willReturn(false);

        $this->gamePlayService->completeTask($user, $userGame, $task, $requestDto);
    }
}

