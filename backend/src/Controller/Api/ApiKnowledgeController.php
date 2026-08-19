<?php

namespace App\Controller\Api;

use App\Entity\KnowledgeArticle;
use App\Entity\KnowledgeCategory;
use App\Entity\User;
use App\Service\KnowledgeBaseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/knowledge')]
class ApiKnowledgeController extends AbstractController
{
    #[Route('', name: 'api_knowledge_index', methods: ['GET'])]
    public function index(KnowledgeBaseService $kbService, EntityManagerInterface $em): JsonResponse
    {
        $categories = $em->getRepository(KnowledgeCategory::class)->findBy(['isActive' => true], ['sortOrder' => 'ASC']);
        $popular = $kbService->getPopularArticles(5);
        $recent = $kbService->getRecentArticles(5);

        return $this->json([
            'categories' => array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'description' => $c->getDescription(),
                'icon' => $c->getIcon(),
            ], $categories),
            'popularArticles' => array_map(fn($a) => $this->serializeArticle($a), $popular),
            'recentArticles' => array_map(fn($a) => $this->serializeArticle($a), $recent),
        ]);
    }

    #[Route('/search', name: 'api_knowledge_search', methods: ['GET'])]
    public function search(Request $request, KnowledgeBaseService $kbService): JsonResponse
    {
        $q = $request->query->get('q', '');
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(50, (int) $request->query->get('limit', 15)));

        $res = $kbService->searchArticles($q, $categoryId, $page, $limit);

        return $this->json([
            'items' => array_map(fn($a) => $this->serializeArticle($a), $res['items']),
            'total' => $res['total'],
            'page' => $res['page'],
            'limit' => $res['limit'],
            'hasMore' => $res['hasMore'],
        ]);
    }

    #[Route('/categories', name: 'api_knowledge_categories', methods: ['GET'])]
    public function categories(EntityManagerInterface $em): JsonResponse
    {
        $categories = $em->getRepository(KnowledgeCategory::class)->findBy(['isActive' => true], ['sortOrder' => 'ASC']);
        return $this->json(array_map(fn($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'slug' => $c->getSlug(),
            'description' => $c->getDescription(),
            'icon' => $c->getIcon(),
        ], $categories));
    }

    #[Route('/articles/{slug}', name: 'api_knowledge_article_show', methods: ['GET'])]
    public function show(string $slug, KnowledgeBaseService $kbService): JsonResponse
    {
        $article = $kbService->getPublishedArticle($slug);
        if (!$article) {
            return $this->json(['error' => 'Article not found or not published'], Response::HTTP_NOT_FOUND);
        }

        $kbService->incrementViewCount($article);
        $related = $kbService->getRelatedArticles($article, 4);

        $data = $this->serializeArticle($article, true);
        $data['relatedArticles'] = array_map(fn($a) => $this->serializeArticle($a), $related);

        return $this->json($data);
    }

    #[Route('/articles/{id}/feedback', name: 'api_knowledge_article_feedback', methods: ['POST'])]
    public function feedback(int $id, Request $request, KnowledgeBaseService $kbService, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $article = $em->getRepository(KnowledgeArticle::class)->find($id);
        if (!$article || $article->getStatus() !== 'PUBLISHED') {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?: $request->request->all();
        $helpful = filter_var($body['helpful'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $res = $kbService->submitFeedback($article, $user, $helpful);
        return $this->json($res);
    }

    private function serializeArticle(KnowledgeArticle $a, bool $details = false): array
    {
        $data = [
            'id' => $a->getId(),
            'title' => $a->getTitle(),
            'slug' => $a->getSlug(),
            'excerpt' => $a->getExcerpt(),
            'categoryName' => $a->getCategory()->getName(),
            'categorySlug' => $a->getCategory()->getSlug(),
            'viewCount' => $a->getViewCount(),
            'helpfulCount' => $a->getHelpfulCount(),
            'notHelpfulCount' => $a->getNotHelpfulCount(),
            'helpfulPercentage' => $a->getHelpfulPercentage(),
            'publishedAt' => $a->getPublishedAt() ? $a->getPublishedAt()->format('Y-m-d H:i:s') : null,
        ];

        if ($details) {
            $data['content'] = $a->getContent();
            $data['authorName'] = $a->getCreatedBy()->getFullName();
        }

        return $data;
    }
}
