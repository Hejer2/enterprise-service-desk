<?php

namespace App\Repository;

use App\Entity\RecurringTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecurringTicket>
 */
class RecurringTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringTicket::class);
    }

    public function findDueRecurringTickets(): array
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('r')
            ->where('r.isActive = true')
            ->andWhere('r.nextRunAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
