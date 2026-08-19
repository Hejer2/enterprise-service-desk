<?php

namespace App\Controller;

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

#[Route('/knowledge')]
class KnowledgeController extends AbstractController
{
    #[Route('', name: 'app_knowledge_index', methods: ['GET'])]
    public function index(KnowledgeBaseService $kbService, EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(KnowledgeCategory::class)->findBy(['isActive' => true], ['sortOrder' => 'ASC']);
        $popularArticles = $kbService->getPopularArticles(5);
        $recentArticles = $kbService->getRecentArticles(5);

        return $this->render('knowledge/index.html.twig', [
            'categories' => $categories,
            'popularArticles' => $popularArticles,
            'recentArticles' => $recentArticles,
        ]);
    }

    #[Route('/search', name: 'app_knowledge_search', methods: ['GET'])]
    public function search(Request $request, KnowledgeBaseService $kbService): Response
    {
        $query = $request->query->get('q', '');
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $page = max(1, (int) $request->query->get('page', 1));

        $res = $kbService->searchArticles($query, $categoryId, $page, 15);

        if ($request->isXmlHttpRequest() || $request->query->get('ajax')) {
            return $this->render('knowledge/_search_results.html.twig', [
                'articles' => $res['items'],
                'total' => $res['total'],
                'query' => $query,
            ]);
        }

        return $this->render('knowledge/search.html.twig', [
            'articles' => $res['items'],
            'total' => $res['total'],
            'page' => $res['page'],
            'hasMore' => $res['hasMore'],
            'query' => $query,
        ]);
    }

    #[Route('/category/{slug}', name: 'app_knowledge_category', methods: ['GET'])]
    public function category(string $slug, KnowledgeBaseService $kbService, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(KnowledgeCategory::class)->findOneBy(['slug' => $slug, 'isActive' => true]);
        if (!$category) {
            throw $this->createNotFoundException('Category not found.');
        }

        $res = $kbService->searchArticles('', $category->getId(), 1, 30);

        return $this->render('knowledge/category.html.twig', [
            'category' => $category,
            'articles' => $res['items'],
            'total' => $res['total'],
        ]);
    }

    #[Route('/article/{slug}', name: 'app_knowledge_article_show', methods: ['GET'])]
    public function show(string $slug, KnowledgeBaseService $kbService): Response
    {
        $article = $kbService->getPublishedArticle($slug);
        if (!$article) {
            throw $this->createNotFoundException('Article not found or not published.');
        }

        $kbService->incrementViewCount($article);
        $relatedArticles = $kbService->getRelatedArticles($article, 4);

        return $this->render('knowledge/article.html.twig', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
        ]);
    }

    #[Route('/article/{id}/feedback', name: 'app_knowledge_article_feedback', methods: ['POST'])]
    public function feedback(int $id, Request $request, KnowledgeBaseService $kbService, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $article = $em->getRepository(KnowledgeArticle::class)->find($id);
        if (!$article || $article->getStatus() !== 'PUBLISHED') {
            return new JsonResponse(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $helpful = filter_var($data['helpful'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $res = $kbService->submitFeedback($article, $user, $helpful);

        return new JsonResponse($res);
    }
}
