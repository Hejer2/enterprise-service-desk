<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketDependency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketDependency>
 */
class TicketDependencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketDependency::class);
    }

    public function findUnresolvedBlockingDependencies(Ticket $ticket): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.dependsOnTicket', 'dep')
            ->where('d.ticket = :ticket')
            ->andWhere('d.dependencyType = :type')
            ->andWhere('dep.status NOT IN (:closedStatuses)')
            ->setParameter('ticket', $ticket)
            ->setParameter('type', 'BLOCKED_BY')
            ->setParameter('closedStatuses', ['Resolved', 'Closed'])
            ->getQuery()
            ->getResult();
    }
}
