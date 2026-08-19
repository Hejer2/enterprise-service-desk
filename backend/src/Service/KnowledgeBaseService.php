<?php

namespace App\Service;

use App\Entity\KnowledgeArticle;
use App\Entity\KnowledgeArticleFeedback;
use App\Entity\KnowledgeCategory;
use App\Entity\User;
use App\Repository\KnowledgeArticleFeedbackRepository;
use App\Repository\KnowledgeArticleRepository;
use App\Repository\KnowledgeCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class KnowledgeBaseService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private KnowledgeArticleRepository $articleRepository,
        private KnowledgeCategoryRepository $categoryRepository,
        private KnowledgeArticleFeedbackRepository $feedbackRepository
    ) {}

    public function searchArticles(string $query = '', ?int $categoryId = null, int $page = 1, int $limit = 20): array
    {
        return $this->articleRepository->searchPublishedArticles($query, $categoryId, $page, $limit);
    }

    public function getPublishedArticle(string $slug): ?KnowledgeArticle
    {
        return $this->articleRepository->findOneBy(['slug' => $slug, 'status' => 'PUBLISHED']);
    }

    public function incrementViewCount(KnowledgeArticle $article): void
    {
        $article->setViewCount($article->getViewCount() + 1);
        $this->entityManager->flush();
    }

    public function submitFeedback(KnowledgeArticle $article, User $user, bool $helpful): array
    {
        $existing = $this->feedbackRepository->findOneBy([
            'article' => $article,
            'user' => $user,
        ]);

        if ($existing) {
            if ($existing->isHelpful() === $helpful) {
                return [
                    'success' => true,
                    'alreadySubmitted' => true,
                    'helpfulCount' => $article->getHelpfulCount(),
                    'notHelpfulCount' => $article->getNotHelpfulCount(),
                    'helpfulPercentage' => $article->getHelpfulPercentage(),
                ];
            }

            // Adjust counters on vote change
            if ($existing->isHelpful() && !$helpful) {
                $article->setHelpfulCount(max(0, $article->getHelpfulCount() - 1));
                $article->setNotHelpfulCount($article->getNotHelpfulCount() + 1);
            } elseif (!$existing->isHelpful() && $helpful) {
                $article->setNotHelpfulCount(max(0, $article->getNotHelpfulCount() - 1));
                $article->setHelpfulCount($article->getHelpfulCount() + 1);
            }
            $existing->setHelpful($helpful);
        } else {
            $feedback = new KnowledgeArticleFeedback();
            $feedback->setArticle($article);
            $feedback->setUser($user);
            $feedback->setHelpful($helpful);

            if ($helpful) {
                $article->setHelpfulCount($article->getHelpfulCount() + 1);
            } else {
                $article->setNotHelpfulCount($article->getNotHelpfulCount() + 1);
            }

            $this->entityManager->persist($feedback);
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'alreadySubmitted' => false,
            'helpfulCount' => $article->getHelpfulCount(),
            'notHelpfulCount' => $article->getNotHelpfulCount(),
            'helpfulPercentage' => $article->getHelpfulPercentage(),
        ];
    }

    public function getPopularArticles(int $limit = 5): array
    {
        return $this->articleRepository->findPopularArticles($limit);
    }

    public function getRecentArticles(int $limit = 5): array
    {
        return $this->articleRepository->findRecentArticles($limit);
    }

    public function getRelatedArticles(KnowledgeArticle $article, int $limit = 4): array
    {
        return $this->articleRepository->findRelatedArticles($article, $limit);
    }

    public function publishArticle(KnowledgeArticle $article, User $actor): void
    {
        $article->setStatus('PUBLISHED');
        $article->setUpdatedBy($actor);
        $this->entityManager->flush();
    }

    public function archiveArticle(KnowledgeArticle $article, User $actor): void
    {
        $article->setStatus('ARCHIVED');
        $article->setUpdatedBy($actor);
        $this->entityManager->flush();
    }
}
