<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegistrationRequestDto;
use App\Entity\User;
use App\Repository\UserTokenRepository;
use App\Service\Exception\EmailAlreadyExistsException;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserTokenRepository $userTokenRepository,
        private readonly RegistrationService $registrationService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        #[Autowire('%gesdinet_jwt_refresh_token.ttl%')]
        private readonly int $refreshTokenTtl,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegistrationRequestDto $dto): JsonResponse
    {
        try {
            $user = $this->registrationService->registerUser($dto);
        } catch (EmailAlreadyExistsException $e) {
            return new JsonResponse([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => $e->getMessage(),
                    'details' => [
                        ['field' => 'email', 'message' => $e->getMessage()],
                    ],
                ],
            ], Response::HTTP_CONFLICT);
        }

        $refreshToken = $this->refreshTokenManager->create();
        $refreshToken->setUsername($user->getUserIdentifier());
        $refreshToken->setRefreshToken();

        $validityPeriod = new \DateInterval(sprintf('PT%sS', $this->refreshTokenTtl));
        $valid = (new \DateTime())->add($validityPeriod);
        $refreshToken->setValid($valid);

        $this->refreshTokenManager->save($refreshToken);

        return new JsonResponse([
            'token' => $this->jwtManager->create($user),
            'refresh_token' => $refreshToken->getRefreshToken(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(#[CurrentUser] User $user): JsonResponse
    {
        $tokens = $this->userTokenRepository->findBy(['username' => $user->getUserIdentifier()]);

        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }

        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
