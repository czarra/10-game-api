<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Game;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Game>
 */
final class GameFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Game::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->words(3, true),
            'description' => self::faker()->paragraph(),
            'isAvailable' => true,
        ];
    }
}
