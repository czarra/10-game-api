<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserGame;
use App\Entity\UserGameTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserGameTask>
 *
 * @method UserGameTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGameTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGameTask[]    findAll()
 * @method UserGameTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGameTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGameTask::class);
    }

    public function findLastCompletedTaskForUserGame(UserGame $userGame): ?UserGameTask
    {
        return $this->createQueryBuilder('ugt')
            ->innerJoin('ugt.gameTask', 'gt')
            ->where('ugt.userGame = :userGame')
            ->setParameter('userGame', $userGame)
            ->orderBy('gt.sequenceOrder', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
