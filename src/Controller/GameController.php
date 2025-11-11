<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GameQueryService;
use App\Validator\UuidValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games')]
final class GameController extends AbstractController
{
    #[Route('/{id}', name: 'api_games_get', methods: ['GET'])]
    public function getGame(
        string $id,
        GameQueryService $gameQueryService,
        UuidValidator $uuidValidator
    ): JsonResponse {
        // Walidacja UUID
        if (!$uuidValidator->validate($id)) {
            return new JsonResponse(['error' => 'Invalid game ID format'], 400);
        }

        $gameDetails = $gameQueryService->getGameDetails($id);
        
        if (!$gameDetails) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        return $this->json($gameDetails);
    }
}
