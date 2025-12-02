<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {

    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->email(),
            'roles' => ['ROLE_USER'],
            'password' => 'password', // Wstępne hasło, zostanie nadpisane przez instrukcje w create() lub afterInstantiate
        ];
    }

    // W Foundry 2.0 lepiej używać mechanizmu initialize() lub directly w defaults,
    // ale zachowując logikę metody withHashedPassword:
    public function withHashedPassword(string $plainPassword = 'password'): self
    {
        return $this->afterInstantiate(function(User $user) use ($plainPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        });
    }

    public function asAdmin(): self
    {
        return $this->with([
            'roles' => ['ROLE_ADMIN'],
        ]);
    }
}
