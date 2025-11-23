<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\GameTask;
use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

#[AsDoctrineListener(event: SoftDeleteableListener::PRE_SOFT_DELETE)]
final class TaskSoftDeleteListener
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function preSoftDelete(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Task) {
            return;
        }

        $uow = $this->em->getUnitOfWork();
        $gameTaskRepository = $this->em->getRepository(GameTask::class);
        $gameTaskMeta = $this->em->getClassMetadata(GameTask::class);

        $gameTasks = $gameTaskRepository->findBy(['task' => $entity]);

        foreach ($gameTasks as $gameTask) {
            if (null === $gameTask->getDeletedAt()) {
                $gameTask->setDeletedAt(new \DateTimeImmutable());
                $uow->persist($gameTask);
                $uow->recomputeSingleEntityChangeSet($gameTaskMeta, $gameTask);
            }
        }
    }
}

