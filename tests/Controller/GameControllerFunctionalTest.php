<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Factory\GameFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\UserGameFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class GameControllerFunctionalTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testGetCompletedGamesReturns401ForUnauthenticatedUser(): void
    {
        $client = static::createClient(['exception' => false]);
        $client->request('GET', '/api/games/completed');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCompletedGamesReturnsCorrectDataForAuthenticatedUser(): void
    {
        $client = static::createClient(['exception' => false]);
        $userFactory = static::getContainer()->get(UserFactory::class);
        $user = $userFactory->create()->_real();
        $game = GameFactory::createOne();

        // Create 3 completed games for the user
        $userGameFactory = static::getContainer()->get(UserGameFactory::class);
        $userGameFactory->createMany(3, [
            'user' => $user,
            'game' => $game,
            'completedAt' => new \DateTimeImmutable(),
        ]);
        
        // Create an incomplete game that should not appear in results
        $userGameFactory = static::getContainer()->get(UserGameFactory::class);
        $userGameFactory->createOne([
            'user' => $user,
            'game' => $game,
            'completedAt' => null,
        ]);
        
        // Create a completed game for another user that should not appear
        $userGameFactory = static::getContainer()->get(UserGameFactory::class);
        $userGameFactory->createOne(['completedAt' => new \DateTimeImmutable()]);

        $client->loginUser($user);
        $client->request('GET', '/api/games/completed');

        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        $this->assertCount(3, $data['data']);
        $this->assertSame(1, $data['pagination']['page']);
        $this->assertSame(10, $data['pagination']['limit']);
        $this->assertSame(3, $data['pagination']['total']);
        $this->assertSame(1, $data['pagination']['pages']);

        // Check if the game name is present in the first result
        $this->assertSame($game->getName(), $data['data'][0]['gameName']);
    }
    
    public function testGetCompletedGamesWithCustomPagination(): void
    {
        $client = static::createClient(['exception' => false]);
        $userFactory = static::getContainer()->get(UserFactory::class);
        $user = $userFactory->create()->_real();
        $game = GameFactory::createOne();

        $userGameFactory = static::getContainer()->get(UserGameFactory::class);
        $userGameFactory->createMany(5, [
            'user' => $user,
            'game' => $game,
            'completedAt' => new \DateTimeImmutable(),
        ]);

        $client->loginUser($user);
        $client->request('GET', '/api/games/completed?page=2&limit=3');

        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $data = json_decode($responseContent, true);
        
        $this->assertCount(2, $data['data']);
        $this->assertSame(2, $data['pagination']['page']);
        $this->assertSame(3, $data['pagination']['limit']);
        $this->assertSame(5, $data['pagination']['total']);
        $this->assertSame(2, $data['pagination']['pages']);
    }

    public function testGetCompletedGamesReturns400ForInvalidLimit(): void
    {
        $client = static::createClient(['exception' => false]);
        $userFactory = static::getContainer()->get(UserFactory::class);
        $user = $userFactory->create()->_real();
        
        $client->loginUser($user);
        $client->request('GET', '/api/games/completed?limit=100');

        $this->assertResponseStatusCodeSame(400);
        $responseContent = $client->getResponse()->getContent();
        $data = json_decode($responseContent, true);
        
        $this->assertSame('Limit cannot be greater than 50.', $data['error']);
    }
}
