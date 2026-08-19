<?php

namespace App\Repository;

use App\Entity\KnowledgeArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KnowledgeArticle>
 */
class KnowledgeArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnowledgeArticle::class);
    }

    public function searchPublishedArticles(string $query, ?int $categoryId = null, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', 'PUBLISHED')
            ->orderBy('a.publishedAt', 'DESC');

        if (trim($query) !== '') {
            $qb->andWhere('LOWER(a.title) LIKE :query OR LOWER(a.content) LIKE :query OR LOWER(a.excerpt) LIKE :query')
               ->setParameter('query', '%' . strtolower(trim($query)) . '%');
        }

        if ($categoryId !== null) {
            $qb->andWhere('a.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery());
        $total = count($paginator);
        $items = iterator_to_array($paginator);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'hasMore' => ($page * $limit) < $total,
        ];
    }

    public function findPopularArticles(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', 'PUBLISHED')
            ->orderBy('a.viewCount', 'DESC')
            ->addOrderBy('a.helpfulCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecentArticles(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', 'PUBLISHED')
            ->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRelatedArticles(KnowledgeArticle $article, int $limit = 4): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.id != :id')
            ->andWhere('a.category = :category')
            ->setParameter('status', 'PUBLISHED')
            ->setParameter('id', $article->getId())
            ->setParameter('category', $article->getCategory())
            ->orderBy('a.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
