<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotification>
 */
class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    public function findPaginatedForUser(User $user, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery());
        $total = count($paginator);
        $items = iterator_to_array($paginator);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'hasMore' => ($page * $limit) < $total,
        ];
    }

    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsReadForUser(User $user): int
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->update(UserNotification::class, 'n')
            ->set('n.isRead', 'true')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
