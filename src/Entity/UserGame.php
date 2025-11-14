<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserGameRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserGameRepository::class)]
#[ORM\Table(name: 'user_games')]
#[ORM\UniqueConstraint(name: 'user_games_user_id_game_id_unique_active', columns: ['user_id', 'game_id'], options: ['where' => 'completed_at IS NULL'])]
class UserGame
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'started_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'completed_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;
        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }
}
