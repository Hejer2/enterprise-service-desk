<?php

namespace App\Repository;

use App\Entity\SlaPolicy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SlaPolicy>
 */
class SlaPolicyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlaPolicy::class);
    }

    public function findActivePolicyForPriority(string $priority): ?SlaPolicy
    {
        return $this->createQueryBuilder('s')
            ->where('s.priority = :priority')
            ->andWhere('s.isActive = true')
            ->setParameter('priority', $priority)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
