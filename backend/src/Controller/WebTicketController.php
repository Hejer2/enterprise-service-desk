<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\TicketMessage;
use App\Entity\TicketAttachment;
use App\Entity\LeaveRequest;
use App\Entity\LeaveType;
use App\Entity\User;
use App\Repository\TicketActivityRepository;
use App\Service\TicketRouter;
use App\Service\TicketManagerService;
use App\Service\AI\AiService;
use App\Service\NotificationService;
use App\Service\AuditLogger;
use App\Service\TicketActivityLogger;
use App\Service\FileSecurityValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;

class WebTicketController extends AbstractController
{
    #[Route('/tickets', name: 'app_ticket_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $qb = $em->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->orderBy('t.createdAt', 'DESC');

        // Apply role filters
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

        // Search filter
        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('t.ticketNumber LIKE :search OR t.title LIKE :search OR t.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Status filter
        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        // Priority filter
        $priority = $request->query->get('priority');
        if ($priority) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $priority);
        }

        // Category filter
        $category = $request->query->get('category');
        if ($category) {
            $qb->andWhere('t.category = :categoryFilter')
               ->setParameter('categoryFilter', $category);
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

        // Page size setup (10, 25, 50, 100)
        $limit = max(10, min(100, (int)$request->query->get('limit', 25)));
        $page = max(1, (int)$request->query->get('page', 1));
        $qb->setMaxResults($limit)
           ->setFirstResult(($page - 1) * $limit);

        $tickets = $qb->getQuery()->getResult();

        // Count for pagination
        $countQb = clone $qb;
        $countQb->select('COUNT(t.id)')
                ->setMaxResults(null)
                ->setFirstResult(null);
        $totalTickets = (int)$countQb->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int)ceil($totalTickets / $limit));

        // Fetch Technicians for Bulk Assignment
        $techs = $em->createQuery(
            'SELECT u FROM App\Entity\User u JOIN u.roleEntity r WHERE r.name IN (:roles)'
        )
        ->setParameter('roles', ['ROLE_IT_TECH', 'ROLE_MAINTENANCE_TECH', 'ROLE_HR'])
        ->getResult();

        // Fetch TicketSla map for current page
        $ticketSlasMap = [];
        if (!empty($tickets)) {
            $ticketIds = array_map(fn($t) => $t->getId(), $tickets);
            $slas = $em->getRepository(\App\Entity\TicketSla::class)->findBy(['ticket' => $ticketIds]);
            foreach ($slas as $s) {
                if ($s->getTicket()) {
                    $ticketSlasMap[$s->getTicket()->getId()] = $s;
                }
            }
        }

        $params = [
            'tickets' => $tickets,
            'ticket_slas' => $ticketSlasMap,
            'page' => $page,
            'limit' => $limit,
            'sort' => $sortField,
            'order' => $sortOrder,
            'total_tickets' => $totalTickets,
            'total_pages' => $totalPages,
            'search' => $search,
            'selected_status' => $status,
            'selected_priority' => $priority,
            'selected_category' => $category,
            'technicians' => $techs,
        ];

        if ($request->isXmlHttpRequest() || $request->query->get('ajax')) {
            return $this->render('ticket/_ticket_list_table.html.twig', $params);
        }

        return $this->render('ticket/index.html.twig', $params);
    }

    #[Route('/tickets/bulk', name: 'app_ticket_bulk', methods: ['POST'])]
    public function bulkAction(Request $request, TicketManagerService $ticketManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            $data = $request->request->all();
        }

        $action = $data['action'] ?? null;
        $ticketIds = $data['ticketIds'] ?? [];
        $value = $data['value'] ?? null;

        if (!$action || empty($ticketIds) || !is_array($ticketIds)) {
            return new JsonResponse(['error' => 'Invalid bulk request payload'], 400);
        }

        $result = $ticketManager->bulkUpdateTickets($user, $ticketIds, $action, $value);

        return new JsonResponse([
            'success' => count($result['updated']) > 0,
            'updated' => $result['updated'],
            'failed' => $result['failed'],
            'message' => sprintf('%d ticket(s) updated successfully. %d failed.', count($result['updated']), count($result['failed']))
        ]);
    }

    #[Route('/tickets/create', name: 'app_ticket_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em, 
        TicketRouter $router,
        NotificationService $notificationService,
        AuditLogger $auditLogger
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        
        $leaveTypes = $em->getRepository(LeaveType::class)->findAll();

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $category = $request->request->get('category');
            $priority = $request->request->get('priority');
        

            $ticket = new Ticket();
            $ticket->setTitle($title);
            $ticket->setDescription($description);
            $ticket->setCategory($category);
            $ticket->setPriority($priority);
            $ticket->setCreatedBy($user);

            

            // Special Leave Request Handling
            if ($category === 'Leave Request') {
                $leaveTypeId = $request->request->get('leave_type');
                $startDate = $request->request->get('start_date');
                $endDate = $request->request->get('end_date');
                $halfDay = $request->request->get('half_day') === '1';

                if ($leaveTypeId && $startDate && $endDate) {
                    $leaveType = $em->getRepository(LeaveType::class)->find($leaveTypeId);
                    $leaveReq = new LeaveRequest();
                    $leaveReq->setLeaveType($leaveType);
                    $leaveReq->setStartDate(new \DateTimeImmutable($startDate));
                    $leaveReq->setEndDate(new \DateTimeImmutable($endDate));
                    $leaveReq->setHalfDay($halfDay);
                    $ticket->setLeaveRequest($leaveReq);
                }
            }

            // Auto-routing & Assignment
            $router->routeTicket($ticket);

            $em->persist($ticket);
            $em->flush();

            // Handling attachments
            $files = $request->files->get('attachments');
            if ($files) {
                foreach ($files as $file) {
                    if ($file) {
                        if (!FileSecurityValidator::validateUploadedFile($file)) {
                            $this->addFlash('error', 'Attachment rejected: invalid file format or dangerous extension (' . $file->getClientOriginalName() . ').');
                            continue;
                        }
                        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                        try {
                            $file->move(
                                $this->getParameter('kernel.project_dir').'/public/uploads/attachments',
                                $newFilename
                            );

                            $attachment = new TicketAttachment();
                            $attachment->setTicket($ticket);
                            $attachment->setFileName($file->getClientOriginalName());
                            $attachment->setFilePath('/uploads/attachments/'.$newFilename);
                            $attachment->setFileType($file->getClientMimeType());
                            $attachment->setFileSize($file->getSize());
                            $attachment->setUploadedBy($user);

                            $em->persist($attachment);
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Failed to upload attachment: ' . $file->getClientOriginalName());
                        }
                    }
                }
                $em->flush();
            }

            // Logging Audit Log
            $auditLogger->log($user, 'create_ticket', 'Ticket', $ticket->getId(), [
                'ticketNumber' => $ticket->getTicketNumber(),
                'category' => $ticket->getCategory(),
            ]);

            // Notify User & Assigned Tech
            $notificationService->notify(
                $user,
                "Ticket Created Successfully",
                "Your ticket {$ticket->getTicketNumber()} has been logged.",
                'ticket_created',
                $ticket->getId()
            );

            if ($ticket->getAssignedTo()) {
                $notificationService->notify(
                    $ticket->getAssignedTo(),
                    "New Ticket Assigned",
                    "Ticket {$ticket->getTicketNumber()} has been auto-routed and assigned to you.",
                    'ticket_assigned',
                    $ticket->getId()
                );
            }

            $this->addFlash('success', "Ticket {$ticket->getTicketNumber()} has been created!");
            return $this->redirectToRoute('app_ticket_index');
        }

        return $this->render('ticket/create.html.twig', [
            
            'leave_types' => $leaveTypes,
        ]);
    }

    #[Route('/tickets/{id}', name: 'app_ticket_show', methods: ['GET', 'POST'])]
    public function show(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        NotificationService $notificationService,
        AuditLogger $auditLogger,
        TicketActivityLogger $ticketActivityLogger,
        TicketActivityRepository $activityRepo
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found.');
        }

        // Auth check: make sure user has access to view this ticket
        $role = $user->getRoleEntity()->getName();
        if ($role === 'ROLE_EMPLOYEE' && $ticket->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Post response/message handler
        if ($request->isMethod('POST') && $request->request->has('message')) {
            $msgContent = $request->request->get('message');
            if (trim($msgContent)) {
                $message = new TicketMessage();
                $message->setTicket($ticket);
                $message->setSender($user);
                $message->setMessage($msgContent);
                $em->persist($message);

                // If Employee replies -> status becomes Open/In Progress
                // If Tech replies -> status becomes Waiting for Employee
                if ($role === 'ROLE_EMPLOYEE' && $ticket->getStatus() === 'Waiting for Employee') {
                    $ticket->setStatus('In Progress');
                } elseif (($role === 'ROLE_IT_TECH' || $role === 'ROLE_MAINTENANCE_TECH') && $ticket->getStatus() !== 'Resolved') {
                    $ticket->setStatus('Waiting for Employee');
                }

                $em->flush();

                $ticketActivityLogger->logActivity(
                    ticket: $ticket,
                    actor: $user,
                    eventType: 'comment_added',
                    previousValue: null,
                    newValue: null,
                    description: sprintf('Reply added by %s', $user->getFullName())
                );

                // File Upload in messages
                $files = $request->files->get('attachments');
                if ($files) {
                    foreach ($files as $file) {
                        if ($file) {
                            if (!FileSecurityValidator::validateUploadedFile($file)) {
                                $this->addFlash('error', 'Attachment rejected: invalid file format or dangerous extension (' . $file->getClientOriginalName() . ').');
                                continue;
                            }
                            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                            try {
                                $file->move(
                                    $this->getParameter('kernel.project_dir').'/public/uploads/attachments',
                                    $newFilename
                                );

                                $attachment = new TicketAttachment();
                                $attachment->setTicket($ticket);
                                $attachment->setMessage($message);
                                $attachment->setFileName($file->getClientOriginalName());
                                $attachment->setFilePath('/uploads/attachments/'.$newFilename);
                                $attachment->setFileType($file->getClientMimeType());
                                $attachment->setFileSize($file->getSize());
                                $attachment->setUploadedBy($user);

                                $em->persist($attachment);

                                $ticketActivityLogger->logActivity(
                                    ticket: $ticket,
                                    actor: $user,
                                    eventType: 'attachment_added',
                                    previousValue: null,
                                    newValue: $file->getClientOriginalName(),
                                    description: sprintf('Attachment %s uploaded by %s', $file->getClientOriginalName(), $user->getFullName()),
                                    metadata: ['fileName' => $file->getClientOriginalName(), 'fileType' => $file->getClientMimeType()]
                                );
                            } catch (FileException $e) {
                                $this->addFlash('error', 'Failed to upload attachment: ' . $file->getClientOriginalName());
                            }
                        }
                    }
                    $em->flush();
                }

                // Notification routing
                $recipient = ($user === $ticket->getCreatedBy()) ? $ticket->getAssignedTo() : $ticket->getCreatedBy();
                if ($recipient) {
                    $notificationService->notify(
                        $recipient,
                        "New Message on Ticket",
                        "{$user->getFullName()} replied to ticket {$ticket->getTicketNumber()}.",
                        'message_received',
                        $ticket->getId()
                    );
                }

                $auditLogger->log($user, 'reply_ticket', 'Ticket', $ticket->getId(), [
                    'messageId' => $message->getId(),
                ]);

                $this->addFlash('success', 'Reply posted!');
                return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
            }
        }

        // Fetch technicians list for transfer dropdown (IT for IT tech, maintenance for maintenance tech, etc.)
        $technicians = [];
        if ($role === 'ROLE_ADMIN' || $role === 'ROLE_IT_TECH' || $role === 'ROLE_MAINTENANCE_TECH') {
            $targetRole = ($ticket->getCategory() === 'Machine Maintenance') ? 'ROLE_MAINTENANCE_TECH' : 'ROLE_IT_TECH';
            $technicians = $em->createQuery(
                'SELECT u FROM App\Entity\User u JOIN u.roleEntity r WHERE r.name = :role'
            )
            ->setParameter('role', $targetRole)
            ->getResult();
        }

        $activities = $activityRepo->findPaginatedByTicket($ticket, 1, 50)['items'];
        $sla = $em->getRepository(\App\Entity\TicketSla::class)->findOneBy(['ticket' => $ticket]);

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'messages' => $em->getRepository(TicketMessage::class)->findBy(['ticket' => $ticket], ['createdAt' => 'ASC']),
            'technicians' => $technicians,
            'activities' => $activities,
            'sla' => $sla,
        ]);
    }

    #[Route('/tickets/{id}/status', name: 'app_ticket_status', methods: ['POST'])]
    public function updateStatus(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        NotificationService $notificationService,
        AuditLogger $auditLogger
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        $newStatus = $request->request->get('status');

        if ($ticket && $newStatus) {
            $oldStatus = $ticket->getStatus();
            $ticket->setStatus($newStatus);
            $em->flush();

            // Log activity
            $auditLogger->log($user, 'change_status', 'Ticket', $ticket->getId(), [
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
            ]);

            // Notify
            $notificationService->notify(
                $ticket->getCreatedBy(),
                "Ticket Status Changed",
                "Your ticket {$ticket->getTicketNumber()} is now: {$newStatus}.",
                'status_changed',
                $ticket->getId()
            );

            $this->addFlash('success', "Ticket status updated to {$newStatus}!");
        }

        return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
    }

    #[Route('/tickets/{id}/assign', name: 'app_ticket_assign', methods: ['POST'])]
    public function assign(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        NotificationService $notificationService,
        AuditLogger $auditLogger
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        $techId = $request->request->get('technician');

        if ($ticket && $techId) {
            $tech = $em->getRepository(User::class)->find($techId);
            if ($tech) {
                $ticket->setAssignedTo($tech);
                $ticket->setStatus('Assigned');
                $em->flush();

                // Log audit
                $auditLogger->log($user, 'assign_ticket', 'Ticket', $ticket->getId(), [
                    'assignedTo' => $tech->getEmail(),
                ]);

                // Notify tech
                $notificationService->notify(
                    $tech,
                    "New Ticket Assigned",
                    "You have been assigned ticket {$ticket->getTicketNumber()}.",
                    'ticket_assigned',
                    $ticket->getId()
                );

                $this->addFlash('success', "Ticket assigned to {$tech->getFullName()}!");
            }
        }

        return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
    }

    #[Route('/tickets/{id}/edit', name: 'app_ticket_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, AuditLogger $auditLogger): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found.');
        }

        if (!$ticket->canBeEditedOrDeletedBy($user)) {
            $this->addFlash('error', 'You cannot edit this ticket (either you are not the owner, or it has a staff response).');
            return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
        }

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $desc = $request->request->get('description');
            $category = $request->request->get('category');
            $priority = $request->request->get('priority');

            if ($title && $desc) {
                $ticket->setTitle($title);
                $ticket->setDescription($desc);
                $ticket->setCategory($category);
                $ticket->setPriority($priority);
                
                $em->flush();
                $auditLogger->log($user, 'edit_ticket', 'Ticket', $ticket->getId());
                $this->addFlash('success', 'Ticket updated successfully.');
                return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
            }
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/tickets/{id}/delete', name: 'app_ticket_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em, AuditLogger $auditLogger): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found.');
        }

        if (!$ticket->canBeEditedOrDeletedBy($user)) {
            $this->addFlash('error', 'You cannot delete this ticket.');
            return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
        }

        $em->remove($ticket);
        $em->flush();

        $auditLogger->log($user, 'delete_ticket', 'Ticket', $id, [
            'number' => $ticket->getTicketNumber(),
        ]);

        $this->addFlash('success', 'Ticket deleted successfully.');
        return $this->redirectToRoute('app_ticket_index');
    }

    #[Route('/messages/{id}/edit', name: 'app_message_edit', methods: ['POST'])]
    public function editMessage(int $id, Request $request, EntityManagerInterface $em, AuditLogger $auditLogger): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $message = $em->getRepository(TicketMessage::class)->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message not found.');
        }

        if (!$message->canBeEditedOrDeletedBy($user)) {
            $this->addFlash('error', 'You cannot edit this message.');
            return $this->redirectToRoute('app_ticket_show', ['id' => $message->getTicket()->getId()]);
        }

        $text = $request->request->get('message');
        if ($text) {
            $message->setMessage($text);
            $message->setIsEdited(true);
            $em->flush();

            $auditLogger->log($user, 'edit_message', 'TicketMessage', $id);
            $this->addFlash('success', 'Message updated successfully.');
        }

        return $this->redirectToRoute('app_ticket_show', ['id' => $message->getTicket()->getId()]);
    }

    #[Route('/messages/{id}/delete', name: 'app_message_delete', methods: ['POST'])]
    public function deleteMessage(int $id, EntityManagerInterface $em, AuditLogger $auditLogger): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $message = $em->getRepository(TicketMessage::class)->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message not found.');
        }

        if (!$message->canBeEditedOrDeletedBy($user)) {
            $this->addFlash('error', 'You cannot delete this message.');
            return $this->redirectToRoute('app_ticket_show', ['id' => $message->getTicket()->getId()]);
        }

        $ticketId = $message->getTicket()->getId();
        $em->remove($message);
        $em->flush();

        $auditLogger->log($user, 'delete_message', 'TicketMessage', $id);
        $this->addFlash('success', 'Message deleted successfully.');

        return $this->redirectToRoute('app_ticket_show', ['id' => $ticketId]);
    }

    #[Route('/tickets/{id}/reopen', name: 'app_ticket_reopen', methods: ['POST'])]
    public function reopen(int $id, Request $request, EntityManagerInterface $em, TicketManagerService $ticketManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found.');
        }

        $reason = $request->request->get('reason');
        try {
            $ticketManager->reopenTicket($ticket, $user, (string) $reason);
            $this->addFlash('success', 'Ticket reopened successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
    }

    #[Route('/tickets/{id}/csat', name: 'app_ticket_csat', methods: ['POST'])]
    public function csat(int $id, Request $request, EntityManagerInterface $em, TicketManagerService $ticketManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found.');
        }

        $rating = (int) $request->request->get('rating', 5);
        $comment = $request->request->get('comment');

        try {
            $ticketManager->submitCsatRating($ticket, $user, $rating, $comment ? (string) $comment : null);
            $this->addFlash('success', 'Thank you for your feedback!');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
    }

    #[Route('/tickets/{id}/ai/{action}', name: 'app_ticket_ai_action', methods: ['POST', 'GET'])]
    public function aiAction(int $id, string $action, Request $request, EntityManagerInterface $em, TicketManagerService $ticketManager, \App\Service\AI\AiService $aiService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
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
            switch ($action) {
                case 'classify':
                    return $this->json($aiService->classifyTicket($ticket));
                case 'summarize':
                    return $this->json($aiService->summarizeTicket($ticket));
                case 'reply':
                    $body = json_decode($request->getContent(), true) ?: $request->request->all();
                    $replyAction = $body['action'] ?? 'generate';
                    $context = $body['context'] ?? null;
                    return $this->json($aiService->generateReply($ticket, $replyAction, $context));
                case 'similar':
                    return $this->json(['similarTickets' => $aiService->findSimilarTickets($ticket, $user)]);
                case 'knowledge':
                    $body = json_decode($request->getContent(), true) ?: $request->request->all();
                    $query = $body['query'] ?? $ticket->getTitle();
                    return $this->json($aiService->askKnowledgeBaseAi($ticket, $query));
                case 'resolution':
                    return $this->json($aiService->recommendResolution($ticket));
                default:
                    return $this->json(['error' => 'Unknown AI action'], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
