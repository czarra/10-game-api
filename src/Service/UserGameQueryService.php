<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UserGameDetailsDto;
use App\Entity\User;
use App\Repository\UserGameRepository;

class UserGameQueryService
{
    public function __construct(
        private readonly UserGameRepository $userGameRepository
    ) {
    }

    /**
     * Retrieves a user's active game.
     *
     * @param User $user The user for whom to retrieve the active game.
     * @return UserGameDetailsDto|null The active game details DTO, or null if no active game is found.
     */
    public function getActiveUserGame(User $user): ?UserGameDetailsDto
    {
        $userGame = $this->userGameRepository->findOneBy(['user' => $user, 'completedAt' => null]);

        if (!$userGame) {
            return null;
        }

        return new UserGameDetailsDto(
            $userGame->getId()->toRfc4122(),
            $userGame->getUser()->getId()->toRfc4122(),
            $userGame->getGame()->getId()->toRfc4122(),
            $userGame->getGame()->getName(),
            $userGame->getStartedAt(),
            $userGame->getCompletedAt()
        );
    }

    /**
     * Retrieves all completed games for a given user.
     *
     * @param User $user The user for whom to retrieve completed games.
     * @return UserGameDetailsDto[] An array of completed game details DTOs.
     */
    public function getCompletedUserGames(User $user): array
    {
        $userGames = $this->userGameRepository->findBy(['user' => $user, 'completedAt' => !null]);
        $userGameDtos = [];

        foreach ($userGames as $userGame) {
            $userGameDtos[] = new UserGameDetailsDto(
                $userGame->getId()->toRfc4122(),
                $userGame->getUser()->getId()->toRfc4122(),
                $userGame->getGame()->getId()->toRfc4122(),
                $userGame->getGame()->getName(),
                $userGame->getStartedAt(),
                $userGame->getCompletedAt()
            );
        }

        return $userGameDtos;
    }
}
