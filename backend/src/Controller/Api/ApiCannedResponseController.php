<?php

namespace App\Controller\Api;

use App\Entity\CannedResponse;
use App\Entity\User;
use App\Repository\CannedResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/canned-responses')]
class ApiCannedResponseController extends AbstractController
{
    #[Route('', name: 'api_canned_response_index', methods: ['GET'])]
    public function index(CannedResponseRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $role = $user->getRoleEntity()?->getName();
        if ($role === 'ROLE_EMPLOYEE') {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $responses = $repo->findActiveResponses();
        $data = [];
        foreach ($responses as $cr) {
            $data[] = [
                'id' => $cr->getId(),
                'title' => $cr->getTitle(),
                'category' => $cr->getCategory(),
                'content' => $cr->getContent(),
                'isActive' => $cr->isActive(),
                'createdAt' => $cr->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'api_canned_response_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $role = $user->getRoleEntity()?->getName();
        if ($role === 'ROLE_EMPLOYEE') {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $body = json_decode($request->getContent(), true);
        $title = $body['title'] ?? null;
        $content = $body['content'] ?? null;
        $category = $body['category'] ?? null;

        if (!$title || !$content) {
            return $this->json(['error' => 'Title and content are required'], Response::HTTP_BAD_REQUEST);
        }

        $canned = new CannedResponse();
        $canned->setTitle(trim($title));
        $canned->setContent(trim($content));
        $canned->setCategory($category ? trim($category) : null);
        $canned->setCreatedBy($user);

        $em->persist($canned);
        $em->flush();

        return $this->json([
            'id' => $canned->getId(),
            'title' => $canned->getTitle(),
            'category' => $canned->getCategory(),
            'content' => $canned->getContent(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_canned_response_delete', methods: ['DELETE'])]
    public function delete(int $id, CannedResponseRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $role = $user->getRoleEntity()?->getName();
        if ($role === 'ROLE_EMPLOYEE') {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $canned = $repo->find($id);
        if (!$canned) {
            return $this->json(['error' => 'Template not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($canned);
        $em->flush();

        return $this->json(['success' => true], Response::HTTP_OK);
    }
}
