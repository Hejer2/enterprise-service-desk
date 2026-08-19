<?php

namespace App\Repository;

use App\Entity\AutomationRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AutomationRule>
 */
class AutomationRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutomationRule::class);
    }

    public function findActiveByTrigger(string $triggerType): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isActive = true')
            ->andWhere('r.triggerType = :trigger')
            ->setParameter('trigger', strtoupper($triggerType))
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
