<?php

namespace App\Controller;

use App\Entity\KnowledgeArticle;
use App\Entity\KnowledgeCategory;
use App\Entity\User;
use App\Service\KnowledgeBaseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/admin/knowledge')]
class AdminKnowledgeController extends AbstractController
{
    private function checkStaffAccess(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Authentication required.');
        }

        $role = $user->getRoleEntity()?->getName();
        if (!in_array($role, ['ROLE_ADMIN', 'ROLE_IT_TECH', 'ROLE_MAINTENANCE_TECH', 'ROLE_HR'])) {
            throw new AccessDeniedException('Access denied. Staff role required to manage Knowledge Base.');
        }

        return $user;
    }

    #[Route('', name: 'app_admin_knowledge_index', methods: ['GET'])]
    #[Route('/articles', name: 'app_admin_knowledge_articles', methods: ['GET'])]
    public function articles(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->checkStaffAccess();
        $status = $request->query->get('status');
        $categoryId = $request->query->get('category');
        $search = $request->query->get('search');

        $qb = $em->createQueryBuilder()
            ->select('a')
            ->from(KnowledgeArticle::class, 'a')
            ->orderBy('a.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('a.status = :status')->setParameter('status', strtoupper($status));
        }

        if ($categoryId) {
            $qb->andWhere('a.category = :catId')->setParameter('catId', $categoryId);
        }

        if ($search) {
            $qb->andWhere('LOWER(a.title) LIKE :s')->setParameter('s', '%' . strtolower($search) . '%');
        }

        $articles = $qb->getQuery()->getResult();
        $categories = $em->getRepository(KnowledgeCategory::class)->findAll();

        return $this->render('knowledge/admin/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'selected_status' => $status,
            'selected_category' => $categoryId,
            'search' => $search,
        ]);
    }

    #[Route('/articles/new', name: 'app_admin_knowledge_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, KnowledgeBaseService $kbService): Response
    {
        $user = $this->checkStaffAccess();
        $categories = $em->getRepository(KnowledgeCategory::class)->findBy(['isActive' => true]);

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $categoryId = $request->request->get('category');
            $excerpt = $request->request->get('excerpt');
            $content = $request->request->get('content');
            $status = $request->request->get('status', 'DRAFT');

            if ($title && $categoryId && $content) {
                $cat = $em->getRepository(KnowledgeCategory::class)->find($categoryId);
                $article = new KnowledgeArticle();
                $article->setTitle($title);
                $article->setCategory($cat);
                $article->setExcerpt($excerpt);
                $article->setContent($content);
                $article->setStatus($status);
                $article->setCreatedBy($user);

                $em->persist($article);
                $em->flush();

                $this->addFlash('success', 'Knowledge Article created successfully.');
                return $this->redirectToRoute('app_admin_knowledge_articles');
            } else {
                $this->addFlash('error', 'Please fill in all required fields (Title, Category, Content).');
            }
        }

        return $this->render('knowledge/admin/form.html.twig', [
            'article' => null,
            'categories' => $categories,
        ]);
    }

    #[Route('/articles/{id}/edit', name: 'app_admin_knowledge_article_edit', methods: ['GET', 'POST'])]
    public function edit(KnowledgeArticle $article, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->checkStaffAccess();
        $categories = $em->getRepository(KnowledgeCategory::class)->findBy(['isActive' => true]);

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $categoryId = $request->request->get('category');
            $excerpt = $request->request->get('excerpt');
            $content = $request->request->get('content');

            if ($title && $categoryId && $content) {
                $cat = $em->getRepository(KnowledgeCategory::class)->find($categoryId);
                $article->setTitle($title);
                $article->setCategory($cat);
                $article->setExcerpt($excerpt);
                $article->setContent($content);
                $article->setUpdatedBy($user);

                $em->flush();

                $this->addFlash('success', 'Knowledge Article updated successfully.');
                return $this->redirectToRoute('app_admin_knowledge_articles');
            }
        }

        return $this->render('knowledge/admin/form.html.twig', [
            'article' => $article,
            'categories' => $categories,
        ]);
    }

    #[Route('/articles/{id}/publish', name: 'app_admin_knowledge_article_publish', methods: ['POST'])]
    public function publish(KnowledgeArticle $article, KnowledgeBaseService $kbService): Response
    {
        $user = $this->checkStaffAccess();
        $kbService->publishArticle($article, $user);

        $this->addFlash('success', sprintf('Article "%s" is now PUBLISHED.', $article->getTitle()));
        return $this->redirectToRoute('app_admin_knowledge_articles');
    }

    #[Route('/articles/{id}/archive', name: 'app_admin_knowledge_article_archive', methods: ['POST'])]
    public function archive(KnowledgeArticle $article, KnowledgeBaseService $kbService): Response
    {
        $user = $this->checkStaffAccess();
        $kbService->archiveArticle($article, $user);

        $this->addFlash('success', sprintf('Article "%s" has been ARCHIVED.', $article->getTitle()));
        return $this->redirectToRoute('app_admin_knowledge_articles');
    }

    #[Route('/articles/{id}/delete', name: 'app_admin_knowledge_article_delete', methods: ['POST'])]
    public function delete(KnowledgeArticle $article, EntityManagerInterface $em): Response
    {
        $user = $this->checkStaffAccess();
        $role = $user->getRoleEntity()?->getName();

        if ($role !== 'ROLE_ADMIN' && $article->getCreatedBy() !== $user) {
            throw new AccessDeniedException('Only administrators or article creators can delete articles.');
        }

        $em->remove($article);
        $em->flush();

        $this->addFlash('success', 'Article deleted successfully.');
        return $this->redirectToRoute('app_admin_knowledge_articles');
    }
}
