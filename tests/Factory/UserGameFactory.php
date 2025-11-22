<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\UserGame;
use App\Repository\UserGameRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<UserGame>
 *
 * @method        UserGame|Proxy create(array|callable $attributes = [])
 * @method static UserGame|Proxy createOne(array $attributes = [])
 * @method static UserGame|Proxy find(object|array|mixed $criteria)
 * @method static UserGame|Proxy findOrCreate(array $attributes)
 * @method static UserGame|Proxy first(string $sortedField = 'id')
 * @method static UserGame|Proxy last(string $sortedField = 'id')
 * @method static UserGame|Proxy random(array $attributes = [])
 * @method static UserGame|Proxy randomOrCreate(array $attributes = [])
 * @method static UserGameRepository|RepositoryProxy repository()
 * @method static UserGame[]|Proxy[] all()
 * @method static UserGame[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static UserGame[]|Proxy[] findBy(array $attributes)
 * @method static UserGame[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static UserGame[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 */
final class UserGameFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'game' => GameFactory::new(),
            'completedAt' => null,
        ];
    }

    protected static function getClass(): string
    {
        return UserGame::class;
    }
}
