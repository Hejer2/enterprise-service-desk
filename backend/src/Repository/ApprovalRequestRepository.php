<?php

namespace App\Repository;

use App\Entity\ApprovalRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApprovalRequest>
 */
class ApprovalRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApprovalRequest::class);
    }

    public function findPendingForUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('(a.approver = :user OR a.approver IS NULL)')
            ->setParameter('status', 'PENDING')
            ->setParameter('user', $user)
            ->orderBy('a.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
