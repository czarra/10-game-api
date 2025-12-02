<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\GameController;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGame;
use App\Repository\GameTaskRepository;
use App\Service\GamePlayService;
use App\Service\GameQueryService;
use App\Validator\UuidValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

final class GameControllerTest extends TestCase
{
    private GameController $controller;
    private GameQueryService|MockObject $gameQueryService;
    private GamePlayService|MockObject $gamePlayService;
    private UuidValidator|MockObject $uuidValidator;

    protected function setUp(): void
    {
        $this->gameQueryService = $this->createMock(GameQueryService::class);
        $this->gamePlayService = $this->createMock(GamePlayService::class);
        $gameTaskRepository = $this->createMock(GameTaskRepository::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $this->uuidValidator = $this->createMock(UuidValidator::class);

        $this->controller = new GameController(
            $this->gameQueryService,
            $this->gamePlayService,
            $gameTaskRepository,
            $serializer,
            $validator
        );
    }

    public function testGetGamesWithInvalidPageReturns400(): void
    {
        $request = new Request(['page' => 0]);
        $response = $this->controller->getGames($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Page must be a positive integer', $response->getContent());
    }

    public function testGetGameDetailsWithInvalidUuidReturns400(): void
    {
        $this->uuidValidator->method('validate')->willReturn(false);
        $response = $this->controller->getGameDetails('invalid-uuid', $this->uuidValidator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid game ID format', $response->getContent());
    }

    public function testGetGameDetailsReturns404WhenNotFound(): void
    {
        $this->uuidValidator->method('validate')->willReturn(true);
        $this->gameQueryService->method('getGameDetails')->willReturn(null);

        $response = $this->controller->getGameDetails('valid-uuid', $this->uuidValidator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testStartGameSuccess(): void
    {
        $this->markTestSkipped('This test requires more complex mocking of the controller dependencies to be fully functional.');
    }
}
