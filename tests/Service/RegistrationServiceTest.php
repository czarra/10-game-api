<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\RegistrationRequestDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Exception\EmailAlreadyExistsException;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;
    private RegistrationService $registrationService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->registrationService = new RegistrationService(
            $this->userRepository,
            $this->passwordHasher,
            $this->entityManager
        );
    }

    public function testRegisterUserThrowsExceptionIfEmailExists(): void
    {
        $this->expectException(EmailAlreadyExistsException::class);

        $dto = new RegistrationRequestDto();
        $dto->email = 'test@example.com';
        $dto->password = 'password123';

        $this->userRepository->method('findOneBy')->willReturn(new User());

        $this->registrationService->registerUser($dto);
    }

    public function testRegisterUserSuccessfully(): void
    {
        $dto = new RegistrationRequestDto();
        $dto->email = 'new@example.com';
        $dto->password = 'password123';
        $hashedPassword = 'hashed_password';

        $this->userRepository->method('findOneBy')->willReturn(null);
        $this->passwordHasher->method('hashPassword')->willReturn($hashedPassword);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->registrationService->registerUser($dto);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($dto->email, $user->getEmail());
        $this->assertSame($hashedPassword, $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }
}
