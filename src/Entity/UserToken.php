<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity(repositoryClass: UserTokenRepository::class)]
#[ORM\Table(name: 'user_tokens')]
class UserToken extends BaseRefreshToken
{
}
