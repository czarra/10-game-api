<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Game;
use App\Repository\GameRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Game>
 *
 * @method        Game|Proxy create(array|callable $attributes = [])
 * @method static Game|Proxy createOne(array $attributes = [])
 * @method static Game|Proxy find(object|array|mixed $criteria)
 * @method static Game|Proxy findOrCreate(array $attributes)
 * @method static Game|Proxy first(string $sortedField = 'id')
 * @method static Game|Proxy last(string $sortedField = 'id')
 * @method static Game|Proxy random(array $attributes = [])
 * @method static Game|Proxy randomOrCreate(array $attributes = [])
 * @method static GameRepository|RepositoryProxy repository()
 * @method static Game[]|Proxy[] all()
 * @method static Game[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Game[]|Proxy[] findBy(array $attributes)
 * @method static Game[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Game[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 */
final class GameFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->words(3, true),
            'description' => self::faker()->paragraph(),
            'isAvailable' => true,
        ];
    }

    protected static function getClass(): string
    {
        return Game::class;
    }
}
