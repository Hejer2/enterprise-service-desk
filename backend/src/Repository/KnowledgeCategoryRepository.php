<?php

namespace App\Repository;

use App\Entity\KnowledgeCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KnowledgeCategory>
 */
class KnowledgeCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnowledgeCategory::class);
    }

    public function findActiveCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = true')
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
