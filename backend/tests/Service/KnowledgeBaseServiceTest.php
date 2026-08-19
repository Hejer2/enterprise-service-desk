<?php

namespace App\Tests\Service;

use App\Entity\KnowledgeArticle;
use App\Entity\KnowledgeArticleFeedback;
use App\Entity\KnowledgeCategory;
use App\Entity\User;
use App\Repository\KnowledgeArticleFeedbackRepository;
use App\Repository\KnowledgeArticleRepository;
use App\Repository\KnowledgeCategoryRepository;
use App\Service\KnowledgeBaseService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class KnowledgeBaseServiceTest extends TestCase
{
    private $em;
    private $articleRepo;
    private $categoryRepo;
    private $feedbackRepo;
    private $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->articleRepo = $this->createMock(KnowledgeArticleRepository::class);
        $this->categoryRepo = $this->createMock(KnowledgeCategoryRepository::class);
        $this->feedbackRepo = $this->createMock(KnowledgeArticleFeedbackRepository::class);

        $this->service = new KnowledgeBaseService(
            $this->em,
            $this->articleRepo,
            $this->categoryRepo,
            $this->feedbackRepo
        );
    }

    public function testHelpfulPercentageCalculation(): void
    {
        $article = new KnowledgeArticle();
        $article->setHelpfulCount(8);
        $article->setNotHelpfulCount(2);

        $this->assertEquals(80.0, $article->getHelpfulPercentage());
    }

    public function testPublishAndArchiveLifecycle(): void
    {
        $user = new User();
        $article = new KnowledgeArticle();
        $article->setStatus('DRAFT');

        $this->service->publishArticle($article, $user);
        $this->assertEquals('PUBLISHED', $article->getStatus());
        $this->assertNotNull($article->getPublishedAt());

        $this->service->archiveArticle($article, $user);
        $this->assertEquals('ARCHIVED', $article->getStatus());
    }
}
