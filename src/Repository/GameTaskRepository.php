<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameTask>
 *
 * @method GameTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method GameTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method GameTask[]    findAll()
 * @method GameTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameTask::class);
    }

    public function findFirstTaskForGame(Game $game): ?GameTask
    {
        return $this->createQueryBuilder('gt')
            ->andWhere('gt.game = :game')
            ->orderBy('gt.sequenceOrder', 'ASC')
            ->setParameter('game', $game)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNextTaskInSequence(Game $game, int $currentSequence): ?GameTask
    {
        return $this->createQueryBuilder('gt')
            ->andWhere('gt.game = :game')
            ->andWhere('gt.sequenceOrder > :currentSequence')
            ->setParameter('game', $game)
            ->setParameter('currentSequence', $currentSequence)
            ->orderBy('gt.sequenceOrder', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
