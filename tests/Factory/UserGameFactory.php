<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\UserGame;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<UserGame>
 */
final class UserGameFactory extends PersistentProxyObjectFactory
{
    public function __construct(private readonly UserFactory $userFactory)
    {
    }

    public static function class(): string
    {
        return UserGame::class;
    }

    protected function defaults(): array
    {
        return [
            'user' => $this->userFactory,
            'game' => GameFactory::new(),
            'completedAt' => null,
        ];
    }
}
