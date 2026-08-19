<?php

namespace App\Repository;

use App\Entity\TicketSla;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketSla>
 */
class TicketSlaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketSla::class);
    }

    /**
     * Find active TicketSla records that need SLA evaluation.
     */
    public function findActiveTicketSlas(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.ticket', 't')
            ->where('s.resolutionStatus NOT IN (:completed)')
            ->setParameter('completed', ['COMPLETED', 'CLOSED'])
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
