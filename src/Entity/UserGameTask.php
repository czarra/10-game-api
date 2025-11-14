<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserGameTaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserGameTaskRepository::class)]
#[ORM\Table(name: 'user_game_tasks')]
#[ORM\UniqueConstraint(name: 'user_game_tasks_user_game_id_game_task_id_unique', columns: ['user_game_id', 'game_task_id'])]
class UserGameTask
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: UserGame::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserGame $userGame = null;

    #[ORM\ManyToOne(targetEntity: GameTask::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameTask $gameTask = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'completed_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $completedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserGame(): ?UserGame
    {
        return $this->userGame;
    }

    public function setUserGame(?UserGame $userGame): self
    {
        $this->userGame = $userGame;
        return $this;
    }

    public function getGameTask(): ?GameTask
    {
        return $this->gameTask;
    }

    public function setGameTask(?GameTask $gameTask): self
    {
        $this->gameTask = $gameTask;
        return $this;
    }

    public function getCompletedAt(): \DateTimeImmutable
    {
        return $this->completedAt;
    }
}
