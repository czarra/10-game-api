<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
final class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findAvailableGame(string $id): ?Game
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.gameTasks', 'gt')
            ->leftJoin('gt.task', 't')
            ->addSelect('gt', 't')
            ->where('g.id = :id')
            ->andWhere('g.isAvailable = true')
            ->andWhere('g.deletedAt IS NULL')
            ->andWhere('gt.deletedAt IS NULL')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
