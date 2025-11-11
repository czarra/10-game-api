<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'games')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Game
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255, unique: true)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column]
    private bool $isAvailable = false;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'deleted_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt;

    /**
     * @var Collection<int, GameTask>
     */
    #[ORM\OneToMany(mappedBy: 'game', targetEntity: GameTask::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['sequenceOrder' => 'ASC'])]
    private Collection $gameTasks;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->gameTasks = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
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

    /**
     * @return Collection<int, GameTask>
     */
    public function getGameTasks(): Collection
    {
        return $this->gameTasks;
    }

    public function addGameTask(GameTask $gameTask): self
    {
        if (!$this->gameTasks->contains($gameTask)) {
            $this->gameTasks->add($gameTask);
            $gameTask->setGame($this);
        }

        return $this;
    }

    public function removeGameTask(GameTask $gameTask): self
    {
        if ($this->gameTasks->removeElement($gameTask)) {
            // set the owning side to null (unless already changed)
            if ($gameTask->getGame() === $this) {
                $gameTask->setGame(null);
            }
        }

        return $this;
    }
}
