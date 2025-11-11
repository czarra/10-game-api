<?php

declare(strict_types=1);

namespace App\Repository;

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
}
