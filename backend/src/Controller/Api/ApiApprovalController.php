<?php

namespace App\Controller\Api;

use App\Entity\ApprovalRequest;
use App\Entity\Ticket;
use App\Entity\TicketDependency;
use App\Entity\User;
use App\Service\ApprovalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/approvals')]
class ApiApprovalController extends AbstractController
{
    #[Route('/pending', name: 'api_approvals_pending', methods: ['GET'])]
    public function pending(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $repo = $em->getRepository(ApprovalRequest::class);
        $pending = $repo->findPendingForUser($user);

        return $this->json(array_map(fn($a) => [
            'id' => $a->getId(),
            'ticketId' => $a->getTicket()->getId(),
            'ticketNumber' => $a->getTicket()->getTicketNumber(),
            'ticketTitle' => $a->getTicket()->getTitle(),
            'requestedBy' => $a->getRequestedBy()->getFullName(),
            'reason' => $a->getReason(),
            'status' => $a->getStatus(),
            'requestedAt' => $a->getRequestedAt()->format('Y-m-d H:i:s'),
        ], $pending));
    }

    #[Route('/{id}/respond', name: 'api_approvals_respond', methods: ['POST'])]
    public function respond(int $id, Request $request, ApprovalService $approvalService, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $approval = $em->getRepository(ApprovalRequest::class)->find($id);
        if (!$approval) {
            return $this->json(['error' => 'Approval request not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?: $request->request->all();
        $action = $body['action'] ?? 'APPROVE'; // APPROVE or REJECT
        $comment = $body['comment'] ?? null;

        $approvalService->respondApproval($approval, $user, $action, $comment);

        return $this->json(['success' => true, 'status' => $approval->getStatus()]);
    }
}
