<?php

declare(strict_types=1);

namespace App\Repository;

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
}
