<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class GameControllerTest extends WebTestCase
{
    public function testGetGameReturns401WhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $gameId = Uuid::v4()->toRfc4122();

        $client->request('GET', '/api/games/' . $gameId);

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * This is a placeholder for a full success test as described in the plan.
     * To make it work, you need to:
     * 1. Configure a test database.
     * 2. Load test fixtures (a game with tasks).
     * 3. Implement a way to generate a valid JWT for a test user.
     */
    public function testGetGameSuccess(): void
    {
        $this->markTestIncomplete(
            'This test requires a full test setup with a database and JWT generation.'
        );

        /*
        $client = static::createClient();
        
        // 1. TODO: Get/create a test user and generate a JWT token.
        $token = '...';

        // 2. TODO: Get a game ID from test fixtures.
        $gameId = '...';

        $client->request('GET', '/api/games/' . $gameId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonStructure([
            'id',
            'name',
            'description',
            'tasks' => [
                '*' => ['id', 'name', 'description', 'sequenceOrder']
            ]
        ]);
        */
    }
}
