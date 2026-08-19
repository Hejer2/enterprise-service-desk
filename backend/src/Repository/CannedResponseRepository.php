<?php

namespace App\Repository;

use App\Entity\CannedResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CannedResponse>
 */
class CannedResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CannedResponse::class);
    }

    /**
     * @return CannedResponse[]
     */
    public function findActiveResponses(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
