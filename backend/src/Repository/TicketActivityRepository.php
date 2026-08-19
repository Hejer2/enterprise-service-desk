<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketActivity>
 */
class TicketActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketActivity::class);
    }

    /**
     * Find paginated activities for a given ticket sorted by createdAt DESC
     */
    public function findPaginatedByTicket(Ticket $ticket, int $page = 1, int $limit = 30): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('ta')
            ->where('ta.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('ta.createdAt', 'DESC')
            ->addOrderBy('ta.id', 'DESC');

        // Total count
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(ta.id)')->getQuery()->getSingleScalarResult();

        // Paginated results
        $items = $qb->select('ta')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $hasMore = ($offset + count($items)) < $total;

        return [
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'hasMore' => $hasMore,
        ];
    }
}
