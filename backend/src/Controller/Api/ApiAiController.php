<?php

namespace App\Controller\Api;

use App\Entity\Ticket;
use App\Entity\User;
use App\Service\AI\AiService;
use App\Service\ExecutiveAnalyticsService;
use App\Service\TicketManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ai')]
class ApiAiController extends AbstractController
{
    #[Route('/tickets/{id}/classify', name: 'api_ai_ticket_classify', methods: ['POST'])]
    public function classify(int $id, TicketManagerService $ticketManager, AiService $aiService, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($aiService->classifyTicket($ticket));
    }

    #[Route('/tickets/{id}/summarize', name: 'api_ai_ticket_summarize', methods: ['POST'])]
    public function summarize(int $id, TicketManagerService $ticketManager, AiService $aiService, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($aiService->summarizeTicket($ticket));
    }

    #[Route('/tickets/{id}/reply', name: 'api_ai_ticket_reply', methods: ['POST'])]
    public function reply(int $id, Request $request, TicketManagerService $ticketManager, AiService $aiService, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $body = json_decode($request->getContent(), true) ?: $request->request->all();
        $action = $body['action'] ?? 'generate';
        $context = $body['context'] ?? null;

        return $this->json($aiService->generateReply($ticket, $action, $context));
    }

    #[Route('/tickets/{id}/similar', name: 'api_ai_ticket_similar', methods: ['POST'])]
    public function similar(int $id, TicketManagerService $ticketManager, AiService $aiService, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json(['similarTickets' => $aiService->findSimilarTickets($ticket, $user)]);
    }

    #[Route('/tickets/{id}/knowledge', name: 'api_ai_ticket_knowledge', methods: ['POST'])]
    public function knowledge(int $id, Request $request, TicketManagerService $ticketManager, AiService $aiService, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $body = json_decode($request->getContent(), true) ?: $request->request->all();
        $query = $body['query'] ?? $ticket->getTitle();

        return $this->json($aiService->askKnowledgeBaseAi($ticket, $query));
    }

    #[Route('/tickets/{id}/resolution', name: 'api_ai_ticket_resolution', methods: ['POST'])]
    public function resolution(int $id, TicketManagerService $ticketManager, AiService $aiService, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($aiService->recommendResolution($ticket));
    }

    #[Route('/executive-insights', name: 'api_ai_executive_insights', methods: ['POST'])]
    public function executiveInsights(ExecutiveAnalyticsService $analyticsService, AiService $aiService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $role = $user->getRoleEntity()?->getName();
        if ($role !== 'ROLE_ADMIN' && $role !== 'ROLE_HR') {
            return $this->json(['error' => 'Forbidden: Executive analytics access required.'], Response::HTTP_FORBIDDEN);
        }

        $data = $analyticsService->getAnalyticsData(['preset' => '30_days'], true);
        return $this->json($aiService->generateExecutiveInsights($data));
    }
}
