<?php

namespace App\Repository;

use App\Entity\KnowledgeArticleFeedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KnowledgeArticleFeedback>
 */
class KnowledgeArticleFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnowledgeArticleFeedback::class);
    }
}
