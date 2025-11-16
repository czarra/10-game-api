<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\RegistrationRequestDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Exception\EmailAlreadyExistsException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function registerUser(RegistrationRequestDto $dto): User
    {
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw EmailAlreadyExistsException::create($dto->email);
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}

