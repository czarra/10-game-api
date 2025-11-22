<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\ActiveGameListItemDto;
use App\Dto\CompletedGameListItemDto;
use App\Dto\GameDetailsDto;
use App\Dto\GameListItemDto;
use App\Dto\PaginatedResponseDto;
use App\Dto\PaginationDto;
use App\Entity\Game;
use App\Entity\GameTask;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserGame;
use App\Repository\GameRepository;
use App\Repository\UserGameRepository;
use App\Service\GameQueryService;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

final class GameQueryServiceTest extends TestCase
{
    private GameRepository $gameRepository;
    private UserGameRepository $userGameRepository;
    private LoggerInterface $logger;
    private GameQueryService $gameQueryService;

    protected function setUp(): void
    {
        $this->gameRepository = $this->createMock(GameRepository::class);
        $this->userGameRepository = $this->createMock(UserGameRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->gameQueryService = new GameQueryService(
            $this->gameRepository,
            $this->userGameRepository,
            $this->logger
        );
    }

    public function testGetGameDetailsReturnsNullWhenGameNotFound(): void
    {
        $this->gameRepository->method('findAvailableGame')->willReturn(null);
        $result = $this->gameQueryService->getGameDetails(Uuid::v4()->toRfc4122());
        $this->assertNull($result);
    }

    public function testGetGameDetailsReturnsDtoOnSuccess(): void
    {
        $game = $this->createMock(Game::class);
        $task = $this->createMock(Task::class);
        $gameTask = $this->createMock(GameTask::class);

        $gameId = Uuid::v4();
        $taskId = Uuid::v4();

        $game->method('getId')->willReturn($gameId);
        $game->method('getName')->willReturn('Test Game');
        $game->method('getDescription')->willReturn('Game Description');
        $game->method('getGameTasks')->willReturn(new ArrayCollection([$gameTask]));

        $task->method('getId')->willReturn($taskId);
        $task->method('getName')->willReturn('Test Task');
        $task->method('getDescription')->willReturn('Task Description');
        
        $gameTask->method('getTask')->willReturn($task);
        $gameTask->method('getSequenceOrder')->willReturn(1);
        
        $this->gameRepository->method('findAvailableGame')->willReturn($game);

        $result = $this->gameQueryService->getGameDetails($gameId->toRfc4122());

        $this->assertInstanceOf(GameDetailsDto::class, $result);
        $this->assertSame($gameId->toRfc4122(), $result->id);
        $this->assertSame('Test Game', $result->name);
        $this->assertCount(1, $result->tasks);
        $this->assertSame($taskId->toRfc4122(), $result->tasks[0]->id);
    }

    public function testFindAvailableGamesMapsPaginatorToDto(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(Uuid::v4());
        $game->method('getName')->willReturn('Game Name');
        $game->method('getDescription')->willReturn('Game Desc');

        $paginatorData = [
            [$game, 'tasksCount' => 5]
        ];
        
        $paginator = $this->createMock(Paginator::class);
        $paginator->method('getIterator')->willReturn(new \ArrayIterator($paginatorData));
        $paginator->method('count')->willReturn(1);

        $this->gameRepository->method('createAvailableGamesPaginator')->willReturn($paginator);

        $result = $this->gameQueryService->findAvailableGames(1, 10);

        $this->assertInstanceOf(PaginatedResponseDto::class, $result);
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(GameListItemDto::class, $result->data[0]);
        $this->assertSame(5, $result->data[0]->tasksCount);
        $this->assertInstanceOf(PaginationDto::class, $result->pagination);
        $this->assertSame(1, $result->pagination->total);
    }

    public function testFindActiveGameByIdThrowsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $user = $this->createMock(UserInterface::class);
        $this->userGameRepository->method('findActiveGameDetails')->willReturn(null);
        $this->gameQueryService->findActiveGameById(Uuid::v4()->toRfc4122(), $user);
    }

    public function testFindCompletedForUserHappyPath(): void
    {
        $user = $this->createMock(User::class);
        $game = $this->createMock(Game::class);
        $userGame = $this->createMock(UserGame::class);

        $userGameId = Uuid::v4();
        $gameId = Uuid::v4();
        $startedAt = new DateTimeImmutable('2023-01-01 10:00:00');
        $completedAt = new DateTimeImmutable('2023-01-01 11:00:00');

        $game->method('getId')->willReturn($gameId);
        $game->method('getName')->willReturn('Completed Game');
        
        $userGame->method('getId')->willReturn($userGameId);
        $userGame->method('getGame')->willReturn($game);
        $userGame->method('getStartedAt')->willReturn($startedAt);
        $userGame->method('getCompletedAt')->willReturn($completedAt);

        $paginatorData = [
            [
                $userGame,
                'completionTime' => 3600,
                'totalTasks' => 10,
            ]
        ];

        $paginator = $this->createMock(Paginator::class);
        $paginator->method('getIterator')->willReturn(new \ArrayIterator($paginatorData));
        $paginator->method('count')->willReturn(1);

        $this->userGameRepository
            ->expects($this->once())
            ->method('findCompletedByUserPaginated')
            ->with($user, 1, 10)
            ->willReturn($paginator);

        $result = $this->gameQueryService->findCompletedForUser($user, 1, 10);

        $this->assertInstanceOf(PaginatedResponseDto::class, $result);
        $this->assertCount(1, $result->data);
        
        $dto = $result->data[0];
        $this->assertInstanceOf(CompletedGameListItemDto::class, $dto);
        $this->assertSame($userGameId->toRfc4122(), $dto->userGameId);
        $this->assertSame($gameId->toRfc4122(), $dto->gameId);
        $this->assertSame('Completed Game', $dto->gameName);
        $this->assertSame($startedAt->format(\DateTime::ATOM), $dto->startedAt);
        $this->assertSame($completedAt->format(\DateTime::ATOM), $dto->completedAt);
        $this->assertSame(3600, $dto->completionTime);
        $this->assertSame(10, $dto->totalTasks);

        $pagination = $result->pagination;
        $this->assertInstanceOf(PaginationDto::class, $pagination);
        $this->assertSame(1, $pagination->page);
        $this->assertSame(10, $pagination->limit);
        $this->assertSame(1, $pagination->total);
        $this->assertSame(1, $pagination->pages);
    }

    public function testFindCompletedForUserLogsErrorForInvalidUser(): void
    {
        $invalidUser = $this->createMock(UserInterface::class);
        $invalidUser->method('getUserIdentifier')->willReturn('invalid-user');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('User is not an instance of App\Entity\User', ['userId' => 'invalid-user']);

        $result = $this->gameQueryService->findCompletedForUser($invalidUser, 1, 10);

        $this->assertInstanceOf(PaginatedResponseDto::class, $result);
        $this->assertSame([], $result->data);
        $this->assertInstanceOf(PaginationDto::class, $result->pagination);
        $this->assertSame(0, $result->pagination->total);
    }
}
