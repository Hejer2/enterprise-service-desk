<?php

namespace App\Controller\Api;

use App\Entity\Ticket;
use App\Entity\TicketMessage;
use App\Entity\TicketAttachment;
use App\Entity\LeaveRequest;
use App\Entity\LeaveType;
use App\Entity\User;
use App\Repository\TicketActivityRepository;
use App\Service\TicketRouter;
use App\Service\NotificationService;
use App\Service\AuditLogger;
use App\Service\TicketActivityLogger;
use App\Service\TicketManagerService;
use App\Service\FileSecurityValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tickets')]
class ApiTicketController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'api_ticket_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $qb = $em->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->orderBy('t.createdAt', 'DESC');

        $role = $user->getRoleEntity()->getName();
        if ($role === 'ROLE_EMPLOYEE') {
            $qb->andWhere('t.createdBy = :user')
               ->setParameter('user', $user);
        } elseif ($role === 'ROLE_IT_TECH') {
            $qb->andWhere('t.assignedTo = :user OR (t.category = :category AND t.assignedTo IS NULL)')
               ->setParameter('user', $user)
               ->setParameter('category', 'IT Support');
        } elseif ($role === 'ROLE_MAINTENANCE_TECH') {
            $qb->andWhere('t.assignedTo = :user OR (t.category = :category AND t.assignedTo IS NULL)')
               ->setParameter('user', $user)
               ->setParameter('category', 'Machine Maintenance');
        } elseif ($role === 'ROLE_HR') {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', 'Leave Request');
        }

        // Sorting setup
        $sortField = $request->query->get('sort', 'createdAt');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSorts = ['createdAt' => 't.createdAt', 'updatedAt' => 't.updatedAt', 'priority' => 't.priority', 'status' => 't.status'];
        if (isset($allowedSorts[$sortField])) {
            $qb->orderBy($allowedSorts[$sortField], $sortOrder);
        } else {
            $qb->orderBy('t.createdAt', 'DESC');
        }

        // Pagination setup (10, 25, 50, 100)
        $limit = max(10, min(100, (int)$request->query->get('limit', 25)));
        $page = max(1, (int)$request->query->get('page', 1));
        $qb->setMaxResults($limit)
           ->setFirstResult(($page - 1) * $limit);

        $tickets = $qb->getQuery()->getResult();

        $data = [];
        foreach ($tickets as $t) {
            $data[] = $this->serializeTicket($t);
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/bulk', name: 'api_ticket_bulk', methods: ['POST'])]
    public function bulk(Request $request, TicketManagerService $ticketManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $action = $payload['action'] ?? null;
        $ticketIds = $payload['ticketIds'] ?? [];
        $value = $payload['value'] ?? null;

        if (!$action || empty($ticketIds) || !is_array($ticketIds)) {
            return $this->json(['error' => 'Invalid bulk payload'], Response::HTTP_BAD_REQUEST);
        }

        $result = $ticketManager->bulkUpdateTickets($user, $ticketIds, $action, $value);

        return $this->json([
            'success' => count($result['updated']) > 0,
            'updated' => $result['updated'],
            'failed' => $result['failed'],
            'message' => sprintf('%d ticket(s) updated. %d failed.', count($result['updated']), count($result['failed']))
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_ticket_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em, TicketManagerService $ticketManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($this->serializeTicket($ticket, true), Response::HTTP_OK);
    }

    #[Route('', name: 'api_ticket_create', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em,
        TicketRouter $router,
        NotificationService $notificationService,
        AuditLogger $auditLogger,
        TicketActivityLogger $ticketActivityLogger
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $body = json_decode($request->getContent(), true);
        $title = $body['title'] ?? null;
        $description = $body['description'] ?? null;
        $category = $body['category'] ?? null;
        $priority = $body['priority'] ?? 'Medium';


        if (!$title || !$description || !$category) {
            return $this->json(['error' => 'Missing fields'], Response::HTTP_BAD_REQUEST);
        }

        $ticket = new Ticket();
        $ticket->setTitle($title);
        $ticket->setDescription($description);
        $ticket->setCategory($category);
        $ticket->setPriority($priority);
        $ticket->setCreatedBy($user);



        // Special Leave Request
        if ($category === 'Leave Request' && isset($body['leaveRequest'])) {
            $leaveData = $body['leaveRequest'];
            $leaveTypeId = $leaveData['leaveTypeId'] ?? null;
            $startDate = $leaveData['startDate'] ?? null;
            $endDate = $leaveData['endDate'] ?? null;
            $halfDay = $leaveData['halfDay'] ?? false;

            if ($leaveTypeId && $startDate && $endDate) {
                $leaveType = $em->getRepository(LeaveType::class)->find($leaveTypeId);
                if ($leaveType) {
                    $leaveReq = new LeaveRequest();
                    $leaveReq->setLeaveType($leaveType);
                    $leaveReq->setStartDate(new \DateTimeImmutable($startDate));
                    $leaveReq->setEndDate(new \DateTimeImmutable($endDate));
                    $leaveReq->setHalfDay($halfDay);
                    $ticket->setLeaveRequest($leaveReq);
                }
            }
        }

        $router->routeTicket($ticket);

        $em->persist($ticket);
        $em->flush();

        // Log Ticket Activity
        $ticketActivityLogger->logActivity(
            ticket: $ticket,
            actor: $user,
            eventType: 'ticket_created',
            previousValue: null,
            newValue: $ticket->getStatus(),
            description: sprintf('Ticket %s created', $ticket->getTicketNumber()),
            metadata: ['category' => $category, 'priority' => $priority]
        );

        // Log Audit
        $auditLogger->log($user, 'create_ticket_api', 'Ticket', $ticket->getId());

        // Notify
        $notificationService->notify($user, "Ticket Created", "Ticket {$ticket->getTicketNumber()} logged.", 'ticket_created', $ticket->getId());
        if ($ticket->getAssignedTo()) {
            $notificationService->notify($ticket->getAssignedTo(), "Ticket Assigned", "Ticket {$ticket->getTicketNumber()} assigned to you.", 'ticket_assigned', $ticket->getId());
        }

        return $this->json($this->serializeTicket($ticket), Response::HTTP_CREATED);
    }

    #[Route('/{id}/messages', name: 'api_ticket_messages', methods: ['GET'])]
    public function getMessages(int $id, EntityManagerInterface $em, TicketManagerService $ticketManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $messages = $em->getRepository(TicketMessage::class)->findBy(['ticket' => $ticket], ['createdAt' => 'ASC']);
        
        $data = [];
        foreach ($messages as $m) {
            $attachs = [];
            foreach ($m->getAttachments() as $a) {
                $attachs[] = [
                    'id' => $a->getId(),
                    'fileName' => $a->getFileName(),
                    'filePath' => $a->getFilePath(),
                ];
            }
            
            $data[] = [
                'id' => $m->getId(),
                'sender' => $m->getSender() ? $m->getSender()->getFullName() : 'System',
                'senderEmail' => $m->getSender() ? $m->getSender()->getEmail() : '',
                'senderRole' => $m->getSender()?->getRoleEntity()?->getName() ?? 'ROLE_EMPLOYEE',
                'message' => $m->getMessage(),
                'isEdited' => $m->isEdited(),
                'createdAt' => $m->getCreatedAt() ? $m->getCreatedAt()->format('Y-m-d H:i:s') : (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'attachments' => $attachs,
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}/messages', name: 'api_ticket_reply', methods: ['POST'])]
    public function reply(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        TicketManagerService $ticketManager,
        AuditLogger $auditLogger,
        TicketActivityLogger $ticketActivityLogger
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        try {
            $body = json_decode($request->getContent(), true) ?: $request->request->all();
            $content = $body['message'] ?? $body['content'] ?? $request->request->get('message') ?? $request->request->get('content') ?? null;

            if (!$content || !trim($content)) {
                return $this->json(['error' => 'Message is empty'], Response::HTTP_BAD_REQUEST);
            }

            $message = $ticketManager->addReply($ticket, $user, trim($content));

            // Handle Base64 Attachments in JSON body if present
            $attachmentsData = $body['attachments'] ?? [];
            $uploadedAttachs = [];
            if (is_array($attachmentsData)) {
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/attachments';
                if (!file_exists($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                }

                foreach ($attachmentsData as $att) {
                    $fileName = $att['fileName'] ?? ('file_' . time() . '.png');
                    $base64 = $att['base64'] ?? null;
                    if ($base64) {
                        if (str_contains($base64, ',')) {
                            $base64 = explode(',', $base64)[1];
                        }
                        $decoded = base64_decode($base64, true);
                        if ($decoded !== false && FileSecurityValidator::validateRawFileContent($decoded, $fileName)) {
                            $safeFilename = uniqid('reply_') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $fileName);
                            $fullPath = $uploadDir . '/' . $safeFilename;
                            file_put_contents($fullPath, $decoded);

                            $attachment = new TicketAttachment();
                            $attachment->setTicket($ticket);
                            $attachment->setMessage($message);
                            $attachment->setFileName($fileName);
                            $attachment->setFilePath('/uploads/attachments/' . $safeFilename);
                            $attachment->setFileType($att['fileType'] ?? 'image/png');
                            $attachment->setFileSize(strlen($decoded));
                            $attachment->setUploadedBy($user);

                            $em->persist($attachment);
                            $uploadedAttachs[] = [
                                'id' => $attachment->getId(),
                                'fileName' => $fileName,
                                'filePath' => '/uploads/attachments/' . $safeFilename
                            ];

                            $ticketActivityLogger->logActivity(
                                ticket: $ticket,
                                actor: $user,
                                eventType: 'attachment_added',
                                previousValue: null,
                                newValue: $fileName,
                                description: sprintf('Attachment %s uploaded by %s', $fileName, $user->getFullName()),
                                metadata: ['fileName' => $fileName, 'fileType' => $att['fileType'] ?? 'image/png']
                            );
                        } else {
                            return $this->json(['error' => 'File attachment rejected: invalid format or forbidden extension.'], Response::HTTP_BAD_REQUEST);
                        }
                    }
                }
                $em->flush();
            }

            $auditLogger->log($user, 'reply_ticket_api', 'Ticket', $ticket->getId());

            return $this->json([
                'id' => $message->getId(),
                'sender' => $message->getSender() ? $message->getSender()->getFullName() : 'System',
                'senderEmail' => $message->getSender() ? $message->getSender()->getEmail() : '',
                'senderRole' => $message->getSender()?->getRoleEntity()?->getName() ?? 'ROLE_EMPLOYEE',
                'message' => $message->getMessage(),
                'createdAt' => $message->getCreatedAt() ? $message->getCreatedAt()->format('Y-m-d H:i:s') : (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'attachments' => $uploadedAttachs
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to add message: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/technicians', name: 'api_ticket_technicians', methods: ['GET'])]
    public function getTechnicians(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        $targetRole = 'ROLE_IT_TECH';
        if ($ticket->getCategory() === 'Machine Maintenance') {
            $targetRole = 'ROLE_MAINTENANCE_TECH';
        } elseif ($ticket->getCategory() === 'Leave Request') {
            $targetRole = 'ROLE_HR';
        }

        $technicians = $em->createQuery(
            'SELECT u FROM App\Entity\User u JOIN u.roleEntity r WHERE r.name = :role OR r.name = :adminRole OR r.name = :hrRole'
        )
        ->setParameter('role', $targetRole)
        ->setParameter('adminRole', 'ROLE_ADMIN')
        ->setParameter('hrRole', 'ROLE_HR')
        ->getResult();

        $data = [];
        foreach ($technicians as $tech) {
            $data[] = [
                'id' => $tech->getId(),
                'fullName' => $tech->getFullName(),
                'email' => $tech->getEmail(),
                'role' => $tech->getRoleEntity()->getName(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}/assign', name: 'api_ticket_assign_technician', methods: ['POST'])]
    public function assign(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        TicketManagerService $ticketManager
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        $role = $user->getRoleEntity()?->getName();
        if ($role === 'ROLE_EMPLOYEE' || !$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $body = json_decode($request->getContent(), true);
        $techId = $body['technicianId'] ?? null;

        if (!$techId) {
            return $this->json(['error' => 'Missing technicianId'], Response::HTTP_BAD_REQUEST);
        }

        $tech = $em->getRepository(User::class)->find($techId);
        if (!$tech) {
            return $this->json(['error' => 'Technician not found'], Response::HTTP_NOT_FOUND);
        }

        $ticketManager->assignTechnician($ticket, $user, $tech);

        return $this->json([
            'success' => true,
            'assignedTo' => $tech->getFullName(),
            'status' => $ticket->getStatus()
        ]);
    }

    #[Route('/{id}/status', name: 'api_ticket_update_status', methods: ['POST'])]
    public function updateStatus(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        TicketManagerService $ticketManager
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$ticketManager->isAuthorizedToAccess($user, $ticket)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $body = json_decode($request->getContent(), true);
        $newStatus = $body['status'] ?? null;

        if ($newStatus) {
            $ticketManager->updateStatus($ticket, $user, $newStatus);
            return $this->json(['success' => true, 'status' => $ticket->getStatus()]);
        }

        return $this->json(['error' => 'Missing status field'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}/activities', name: 'api_ticket_activities', methods: ['GET'])]
    public function getActivities(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TicketActivityRepository $activityRepo
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        // Authorization check matching show action
        $role = $user->getRoleEntity()->getName();
        if ($role === 'ROLE_EMPLOYEE' && $ticket->getCreatedBy() !== $user) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 30)));

        $res = $activityRepo->findPaginatedByTicket($ticket, $page, $limit);

        $itemsData = [];
        foreach ($res['items'] as $act) {
            $itemsData[] = [
                'id' => $act->getId(),
                'ticketId' => $ticket->getId(),
                'actor' => $act->getActorData(),
                'eventType' => $act->getEventType(),
                'previousValue' => $act->getPreviousValue(),
                'newValue' => $act->getNewValue(),
                'description' => $act->getDescription(),
                'metadata' => $act->getMetadata(),
                'createdAt' => $act->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json([
            'items' => $itemsData,
            'page' => $res['page'],
            'limit' => $res['limit'],
            'total' => $res['total'],
            'hasMore' => $res['hasMore'],
        ], Response::HTTP_OK);
    }

    private function serializeTicket(Ticket $t, bool $details = false): array
    {
        $res = [
            'id' => $t->getId(),
            'ticketNumber' => $t->getTicketNumber() ?? ('TCK-' . $t->getId()),
            'title' => $t->getTitle() ?? '',
            'description' => $t->getDescription() ?? '',
            'category' => $t->getCategory() ?? 'General Request',
            'priority' => $t->getPriority() ?? 'Medium',
            'status' => $t->getStatus() ?? 'Open',
            'createdBy' => $t->getCreatedBy() ? $t->getCreatedBy()->getFullName() : 'System',
            'assignedTo' => $t->getAssignedTo() ? $t->getAssignedTo()->getFullName() : null,
            'createdAt' => $t->getCreatedAt() ? $t->getCreatedAt()->format('Y-m-d H:i:s') : (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'updatedAt' => $t->getUpdatedAt() ? $t->getUpdatedAt()->format('Y-m-d H:i:s') : (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'closedAt' => $t->getClosedAt() ? $t->getClosedAt()->format('Y-m-d H:i:s') : null,
        ];

        // Safe SLA data serialization
        $sla = null;
        try {
            $sla = $t->getId() ? $this->em->getRepository(\App\Entity\TicketSla::class)->findOneBy(['ticket' => $t]) : null;
        } catch (\Throwable $e) {
            $sla = null;
        }
        if ($sla) {
            $now = new \DateTimeImmutable('now');
            $remainingSeconds = max(0, $sla->getResolutionDueAt()->getTimestamp() - $now->getTimestamp());
            $res['sla'] = [
                'status' => $sla->getResolutionStatus(),
                'firstResponseStatus' => $sla->getFirstResponseStatus(),
                'resolutionStatus' => $sla->getResolutionStatus(),
                'firstResponseDueAt' => $sla->getFirstResponseDueAt()->format('Y-m-d H:i:s'),
                'resolutionDueAt' => $sla->getResolutionDueAt()->format('Y-m-d H:i:s'),
                'remainingMinutes' => (int) ceil($remainingSeconds / 60),
            ];
        }

        if ($details) {
            $res['description'] = $t->getDescription();

            if ($t->getLeaveRequest()) {
                $res['leaveRequest'] = [
                    'leaveType' => $t->getLeaveRequest()->getLeaveType()->getName(),
                    'startDate' => $t->getLeaveRequest()->getStartDate()->format('Y-m-d'),
                    'endDate' => $t->getLeaveRequest()->getEndDate()->format('Y-m-d'),
                    'halfDay' => $t->getLeaveRequest()->isHalfDay(),
                ];
            }
            
            $attachs = [];
            foreach ($t->getAttachments() as $a) {
                if ($a->getMessage() === null) {
                    $attachs[] = [
                        'id' => $a->getId(),
                        'fileName' => $a->getFileName(),
                        'filePath' => $a->getFilePath(),
                        'fileType' => $a->getFileType(),
                        'fileSize' => $a->getFileSize(),
                    ];
                }
            }
            $res['attachments'] = $attachs;
        }

        return $res;
    }

    #[Route('/{id}/reopen', name: 'api_ticket_reopen', methods: ['POST'])]
    public function reopen(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TicketManagerService $ticketManager
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);
        $reason = $body['reason'] ?? null;

        if (!$reason || !trim($reason)) {
            return $this->json(['error' => 'Reason is required to reopen ticket'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ticketManager->reopenTicket($ticket, $user, trim($reason));
            return $this->json([
                'success' => true,
                'status' => $ticket->getStatus(),
                'message' => 'Ticket reopened successfully'
            ], Response::HTTP_OK);
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to reopen ticket'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/csat', name: 'api_ticket_csat', methods: ['POST'])]
    public function csat(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TicketManagerService $ticketManager
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);
        $rating = (int) ($body['rating'] ?? 5);
        $comment = $body['comment'] ?? null;

        try {
            $csat = $ticketManager->submitCsatRating($ticket, $user, $rating, $comment);
            return $this->json([
                'success' => true,
                'id' => $csat->getId(),
                'rating' => $csat->getRating(),
                'comment' => $csat->getComment(),
            ], Response::HTTP_CREATED);
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to submit CSAT rating'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
