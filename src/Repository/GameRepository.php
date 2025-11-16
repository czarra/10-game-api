<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function createAvailableGamesPaginator(int $offset, int $limit): Paginator
    {
        $query = $this->createQueryBuilder('g')
            ->select('g, COUNT(gt.id) as tasksCount')
            ->leftJoin('g.gameTasks', 'gt')
            ->where('g.isAvailable = true')
            ->andWhere('g.deletedAt IS NULL')
            ->andWhere('gt.deletedAt IS NULL')
            ->groupBy('g.id')
            ->orderBy('g.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query);
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
