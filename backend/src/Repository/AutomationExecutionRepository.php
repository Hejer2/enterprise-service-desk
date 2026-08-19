<?php

namespace App\Repository;

use App\Entity\AutomationExecution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AutomationExecution>
 */
class AutomationExecutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutomationExecution::class);
    }
}
