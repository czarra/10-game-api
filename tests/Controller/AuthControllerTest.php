<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Dto\RegistrationRequestDto;
use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserTokenRepository;
use App\Service\Exception\EmailAlreadyExistsException;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use App\Controller\AuthController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AuthControllerTest extends TestCase
{
    private AuthController $controller;
    private EntityManagerInterface $entityManager;
    private UserTokenRepository $userTokenRepository;
    private RegistrationService $registrationService;
    private JWTTokenManagerInterface $jwtManager;
    private RefreshTokenManagerInterface $refreshTokenManager; // Keep for save() mock
    private RefreshTokenGeneratorInterface $refreshTokenGenerator; // New for create() mock

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userTokenRepository = $this->createMock(UserTokenRepository::class);
        $this->registrationService = $this->createMock(RegistrationService::class);
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->refreshTokenGenerator = $this->createMock(RefreshTokenGeneratorInterface::class);

        $this->controller = new AuthController(
            $this->entityManager,
            $this->userTokenRepository,
            $this->registrationService,
            $this->jwtManager,
            $this->refreshTokenManager,
            $this->refreshTokenGenerator,
            3600
        );
    }

    public function testRegisterSuccess(): void
    {
        $dto = new RegistrationRequestDto();
        $dto->email = 'test@example.com';
        $dto->password = 'password';

        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn('test@example.com');
        $user->method('getId')->willReturn(Uuid::v4());
        $refreshToken = $this->createMock(UserToken::class);
        $refreshToken->method('getRefreshToken')->willReturn('fake_refresh_token');

        $this->registrationService->method('registerUser')->willReturn($user);
        // Mock createForUserWithTtl for the generator
        $this->refreshTokenGenerator->method('createForUserWithTtl')->willReturn($refreshToken);
        // Mock save for the manager
        $this->refreshTokenManager->method('save')->with($refreshToken);
        $this->jwtManager->method('create')->willReturn('fake_jwt_token');

        $response = $this->controller->register($dto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertSame('fake_jwt_token', $data['token']);
        $this->assertSame('fake_refresh_token', $data['refresh_token']);
    }

    public function testRegisterConflict(): void
    {
        $dto = new RegistrationRequestDto();
        $dto->email = 'test@example.com';
        $dto->password = 'password';

        $this->registrationService->method('registerUser')->willThrowException(new EmailAlreadyExistsException('test'));

        $response = $this->controller->register($dto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testLogoutSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn('test@example.com');
        $user->method('getId')->willReturn(Uuid::v4()); // Mock ID for comparison in logout test
        $token = new UserToken();

        $this->userTokenRepository->method('findBy')->willReturn([$token]);

        $this->entityManager->expects($this->once())->method('remove')->with($token);
        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->controller->logout($user);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
