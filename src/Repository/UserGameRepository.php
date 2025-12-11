<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameTask;
use App\Entity\User;
use App\Entity\UserGame;
use App\Entity\UserGameTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<UserGame>
 *
 * @method UserGame|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGame|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGame[]    findAll()
 * @method UserGame[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGame::class);
    }

    public function findActiveGameForUser(User $user, Game $game): ?UserGame
    {
        return $this->createQueryBuilder('ug')
            ->andWhere('ug.user = :user')
            ->andWhere('ug.game = :game')
            ->andWhere('ug.completedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('game', $game)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveGamesDetailsByUser(UserInterface $user): array
    {
        // 1. Get active user games and basic details
        $qb = $this->createQueryBuilder('ug');
        $qb->select(
            'ug.id AS userGameId',
            'g.id AS gameId',
            'g.name AS gameName',
            'g.description AS gameDescription',
            'ug.startedAt',
            '(SELECT COUNT(ugt.id) FROM App\Entity\UserGameTask ugt WHERE ugt.userGame = ug) AS completedTasks',
            '(SELECT COUNT(gt.id) FROM App\Entity\GameTask gt WHERE gt.game = g) AS totalTasks'
        )
            ->innerJoin('ug.game', 'g')
            ->where('ug.user = :user')
            ->andWhere('ug.completedAt IS NULL')
            ->setParameter('user', $user)
            ->groupBy('ug.id, g.id');

        $games = $qb->getQuery()->getArrayResult();

        if (empty($games)) {
            return [];
        }

        // 2. Find the next task for all these games in one go
        $userGameIds = array_column($games, 'userGameId');

        $nextTasksQb = $this->getEntityManager()->createQueryBuilder();
        $nextTasksQb
            ->select('ug.id as userGameId, t.id, t.name, t.description, gt.sequenceOrder')
            ->from(UserGame::class, 'ug')
            ->innerJoin('ug.game', 'g')
            ->innerJoin('g.gameTasks', 'gt')
            ->innerJoin('gt.task', 't')
            ->leftJoin(
                'App\Entity\UserGameTask',
                'ugt',
                'WITH',
                'ugt.gameTask = gt AND ugt.userGame = ug'
            )
            ->where('ug.id IN (:userGameIds)')
            ->andWhere('ugt.id IS NULL')
            ->setParameter('userGameIds', $userGameIds)
            ->orderBy('ug.id')
            ->addOrderBy('gt.sequenceOrder', 'ASC');

        $allNextTasks = $nextTasksQb->getQuery()->getResult();

        // We only want the *first* next task for each game
        $nextTaskMap = [];
        foreach ($allNextTasks as $task) {
            /**
             * @var Uuid $task['userGameId']
             */

            if (!isset($nextTaskMap[$task['userGameId']->toRfc4122()])) {
                $nextTaskMap[$task['userGameId']->toRfc4122()] = $task;
            }
        }
        // 3. Combine the results
        foreach ($games as &$game) {
            $game['startedAt'] = $game['startedAt']->format(\DateTime::ATOM);
            $userGameId = $game['userGameId']->toRfc4122();
            if (isset($nextTaskMap[$userGameId])) {
                $taskData = $nextTaskMap[$userGameId];
                $game['currentTask'] = [
                    'id' => $taskData['id'],
                    'name' => $taskData['name'],
                    'description' => $taskData['description'],
                    'sequenceOrder' => $taskData['sequenceOrder'],
                ];
            } else {
                $game['currentTask'] = null;
            }
        }
        return $games;

    }

    public function findActiveGameDetails(string $userGameId, UserInterface $user): ?array
    {
        $qb = $this->createQueryBuilder('ug');

        // Main query to get game details and task counts
        $qb->select(
            'ug.id AS userGameId',
            'g.id AS gameId',
            'g.name AS gameName',
            'g.description AS gameDescription',
            'ug.startedAt',
            '(SELECT COUNT(ugt.id) FROM App\Entity\UserGameTask ugt WHERE ugt.userGame = ug) AS completedTasks',
            '(SELECT COUNT(gt.id) FROM App\Entity\GameTask gt WHERE gt.game = g) AS totalTasks'
        )
            ->innerJoin('ug.game', 'g')
            ->where('ug.user = :user')
            ->andWhere('ug.id = :userGameId')
            ->andWhere('ug.completedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('userGameId', $userGameId, 'uuid');

        $gameDetails = $qb->getQuery()->getOneOrNullResult();

        if (null === $gameDetails) {
            return null;
        }

        // Subquery to find the next task
        $nextTaskQb = $this->getEntityManager()->createQueryBuilder();
        $nextTask = $nextTaskQb
            ->select('t.id', 't.name', 't.description', 'gt.sequenceOrder')
            ->from(GameTask::class, 'gt')
            ->innerJoin('gt.task', 't')
            ->leftJoin(
                UserGameTask::class,
                'ugt',
                'WITH',
                'ugt.gameTask = gt AND ugt.userGame = :userGameId'
            )
            ->where('gt.game = :gameId')
            ->andWhere('ugt.id IS NULL')
            ->orderBy('gt.sequenceOrder', 'ASC')
            ->setParameter('userGameId', $userGameId, 'uuid')
            ->setParameter('gameId', $gameDetails['gameId'], 'uuid')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $gameDetails['currentTask'] = $nextTask;
        $gameDetails['startedAt'] = $gameDetails['startedAt']->format(\DateTime::ATOM);

        return $gameDetails;
    }

    /**
     * @return Paginator<array{0: UserGame, completionTime: int, totalTasks: int}>
     */
    public function findCompletedByUserPaginated(User $user, int $page, int $limit): Paginator
    {
        $qb = $this->createQueryBuilder('ug')
            ->select(
                'ug',
                'EXTRACT(EPOCH FROM (ug.completedAt - ug.startedAt)) AS completionTime',
                'COUNT(gt.id) AS totalTasks'
            )
            ->innerJoin('ug.game', 'g')
            ->leftJoin('g.gameTasks', 'gt')
            ->where('ug.user = :user')
            ->andWhere('ug.completedAt IS NOT NULL')
            ->groupBy('ug.id, g.id')
            ->setParameter('user', $user)
            ->orderBy('ug.completedAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return new Paginator($query, true);
    }
}
