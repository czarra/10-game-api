<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Validator\AtLeastThreeTasks;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'games')]
#[UniqueEntity(fields: ['name'], message: 'This game name is already in use.')]
#[AtLeastThreeTasks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
#[Assert\Callback('validateTaskSequence')]
class Game
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
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
    #[ORM\OneToMany(mappedBy: 'game', targetEntity: GameTask::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
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
        return $this->gameTasks->filter(function(GameTask $gameTask) {
            return null === $gameTask->getDeletedAt();
        });
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

    public function validateTaskSequence(ExecutionContextInterface $context): void
    {
        $sequences = [];
        $taskIds = [];

        foreach ($this->getGameTasks() as $gameTask) {
            // Validate unique sequence order
            $sequenceOrder = $gameTask->getSequenceOrder();
            if (in_array($sequenceOrder, $sequences, true)) {
                $context->buildViolation('Kolejność zadań musi być unikalna. Wartość "{{ value }}" jest zduplikowana.')
                    ->atPath('gameTasks')
                    ->setParameter('{{ value }}', (string) $sequenceOrder)
                    ->addViolation();
            }
            $sequences[] = $sequenceOrder;

            // Validate unique task assignment
            $task = $gameTask->getTask();
            if ($task) {
                $taskId = $task->getId()->__toString();
                if (in_array($taskId, $taskIds, true)) {
                    $context->buildViolation('Zadanie "{{ taskName }}" zostało już przypisane do tej gry.')
                        ->atPath('gameTasks')
                        ->setParameter('{{ taskName }}', $task->getName())
                        ->addViolation();
                }
                $taskIds[] = $taskId;
            }
        }
    }

    public function __toString(): string
    {
        return $this->name ?? 'Nowa Gra';
    }
}
