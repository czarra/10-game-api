<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameTaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GameTaskRepository::class)]
#[ORM\Table(name: 'game_tasks')]
#[ORM\UniqueConstraint(name: 'game_tasks_game_id_sequence_order_unique', columns: ['game_id', 'sequence_order'], options: ['where' => 'deleted_at IS NULL'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class GameTask
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'gameTasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(targetEntity: Task::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Task $task = null;

    #[ORM\Column(type: 'integer')]
    private int $sequenceOrder;

    #[ORM\Column(name: 'deleted_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): self
    {
        $this->task = $task;
        return $this;
    }

    public function getSequenceOrder(): int
    {
        return $this->sequenceOrder;
    }

    public function setSequenceOrder(int $sequenceOrder): self
    {
        $this->sequenceOrder = $sequenceOrder;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }
}
