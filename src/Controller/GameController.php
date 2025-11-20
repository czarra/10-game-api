<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CurrentTaskDto;
use App\Dto\GameDetailsDto;
use App\Dto\StartGameResponseDto;
use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameTaskRepository;
use App\Service\GamePlayService;
use App\Service\GameQueryService;
use App\Validator\UuidValidator;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA; // Importuj alias dla atrybutów
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Dto\TaskCompletionRequestDto;
use App\Entity\Task;
use App\Entity\UserGame;
use Webmozart\Assert\Assert;

#[Route('/api/games')]
#[OA\Tag(name: 'Games')] // Grupuje endpointy w UI
final class GameController extends AbstractController
{
    public function __construct(
        private readonly GameQueryService $gameQueryService,
        private readonly GamePlayService $gamePlayService,
        private readonly GameTaskRepository $gameTaskRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_games_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getGames(Request $request): JsonResponse
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 10);

            Assert::greaterThan($page, 0, 'Page must be a positive integer.');
            Assert::greaterThan($limit, 0, 'Limit must be a positive integer.');
            Assert::lessThanEq($limit, 50, 'Limit cannot be greater than 50.');
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        $games = $this->gameQueryService->findAvailableGames($page, $limit);

        return $this->json($games);
    }

    #[Route('/active', name: 'get_active_games', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getActiveGames(#[CurrentUser] User $user): JsonResponse
    {
        try {

        $activeGames = $this->gameQueryService->findActiveGamesForUser($user);

        }catch (\Throwable $exception){
            dump($exception->getMessage());die;
        }
        return $this->json(['data' => $activeGames]);
    }

    #[Route('/{id}', name: 'api_game_details_get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Szczegółowe informacje o grze',
        content: new OA\JsonContent(
            ref: new Model(type: GameDetailsDto::class)
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Gra o podanym ID nie została znaleziona'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'UUID gry',
        schema: new OA\Schema(type: 'string')
    )]
    #[IsGranted('ROLE_USER')]
    public function getGameDetails(
        string $id,
        UuidValidator $uuidValidator
    ): JsonResponse {
        if (!$uuidValidator->validate($id)) {
            return new JsonResponse(['error' => 'Invalid game ID format'], 400);
        }

        $gameDetails = $this->gameQueryService->getGameDetails($id);

        if (!$gameDetails) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        return $this->json($gameDetails);
    }

    #[Route('/{gameId}/start', name: 'api_user_game_start', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function startGame(
        #[MapEntity(id: 'gameId')] Game $game,
    ): JsonResponse {

        $user = $this->getUser();
        Assert::isInstanceOf($user, User::class);

        $userGame = $this->gamePlayService->startGameForUser($game, $user);
        $firstTask = $this->gameTaskRepository->findFirstTaskForGame($game);

        // This should ideally not happen due to checks in the service, but as a safeguard:
        if (!$firstTask) {
            return new JsonResponse(['error' => 'Cannot start a game with no tasks.'], 400);
        }

        $currentTaskDto = new CurrentTaskDto(
            $firstTask->getId()->toRfc4122(),
            $firstTask->getTask()->getName(),
            $firstTask->getTask()->getDescription(),
            $firstTask->getSequenceOrder()
        );

        $responseDto = new StartGameResponseDto(
            $userGame->getId()->toRfc4122(),
            $game->getId()->toRfc4122(),
            $userGame->getStartedAt()->format(\DateTimeInterface::ATOM),
            $currentTaskDto
        );

        return $this->json($responseDto, 201);
    }

    #[Route('/{userGameId}/tasks/{taskId}/complete', name: 'api_user_game_task_complete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    // Ensure UserGame and Task are automatically resolved from path parameters and security context
    public function completeTask(
        Request $request,
        #[MapEntity(id: 'userGameId')] UserGame $userGame,
        #[MapEntity(id: 'taskId')] Task $task,
        #[CurrentUser] User $user // Current user for service layer
    ): JsonResponse {
        if ($userGame->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You are not allowed to complete tasks for this game session.');
        }

        try {
            /** @var TaskCompletionRequestDto $taskCompletionRequestDto */
            $taskCompletionRequestDto = $this->serializer->deserialize($request->getContent(), TaskCompletionRequestDto::class, 'json');

            $errors = $this->validator->validate($taskCompletionRequestDto);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorMessages], 400);
            }

            $responseDto = $this->gamePlayService->completeTask(
                $user,
                $userGame,
                $task,
                $taskCompletionRequestDto
            );

            return $this->json($responseDto);
        } catch (\Exception $e) {
            // This will be caught by the ExceptionListener, but for now, we'll return a generic error.
            // The ExceptionListener will map custom exceptions to 409, etc.
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
