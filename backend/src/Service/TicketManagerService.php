<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\TicketMessage;
use App\Entity\TicketAttachment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TicketManagerService
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationService $notificationService,
        private AuditLogger $auditLogger,
        private TicketActivityLogger $ticketActivityLogger,
        private ?SlaService $slaService = null,
        private ?RealtimeEventService $realtimeEventService = null
    ) {}

    /**
     * Create ticket and attach SLA.
     */
    public function createTicket(Ticket $ticket): void
    {
        $this->em->persist($ticket);
        $this->em->flush();

        if ($this->slaService) {
            $this->slaService->createTicketSla($ticket);
        }

        if ($this->realtimeEventService) {
            $this->realtimeEventService->publishTicketCreated($ticket);
        }
    }

    /**
     * Check if a user is authorized to view or access a ticket.
     */
    public function isAuthorizedToAccess(User $user, Ticket $ticket): bool
    {
        $role = $user->getRoleEntity()?->getName();
        if ($role === 'ROLE_ADMIN') {
            return true;
        }

        if ($ticket->getCreatedBy() === $user || $ticket->getAssignedTo() === $user) {
            return true;
        }

        if ($role === 'ROLE_EMPLOYEE') {
            return false;
        }

        if ($role === 'ROLE_IT_TECH' && $ticket->getCategory() === 'IT Support') {
            return true;
        }

        if ($role === 'ROLE_MAINTENANCE_TECH' && $ticket->getCategory() === 'Machine Maintenance') {
            return true;
        }

        if ($role === 'ROLE_HR' && $ticket->getCategory() === 'Leave Request') {
            return true;
        }

        return false;
    }

    /**
     * Post a reply to a ticket, update status accordingly, and log activity.
     */
    public function addReply(Ticket $ticket, User $sender, string $content): TicketMessage
    {
        $message = new TicketMessage();
        $message->setTicket($ticket);
        $message->setSender($sender);
        $message->setMessage($content);

        $role = $sender->getRoleEntity()?->getName();
        if ($role === 'ROLE_EMPLOYEE' && $ticket->getStatus() === 'Waiting for Employee') {
            $ticket->setStatus('In Progress');
        } elseif (($role === 'ROLE_IT_TECH' || $role === 'ROLE_MAINTENANCE_TECH' || $role === 'ROLE_ADMIN' || $role === 'ROLE_HR') && $ticket->getStatus() !== 'Resolved') {
            $ticket->setStatus('Waiting for Employee');

            // Record First Response SLA if this is the first technician reply
            if ($this->slaService && $sender !== $ticket->getCreatedBy()) {
                $this->slaService->recordFirstResponse($ticket, $sender);
            }
        }

        $this->em->persist($message);
        $this->em->flush();

        $this->ticketActivityLogger->logActivity(
            ticket: $ticket,
            actor: $sender,
            eventType: 'comment_added',
            previousValue: null,
            newValue: null,
            description: sprintf('Reply added by %s', $sender->getFullName())
        );

        $recipient = ($sender === $ticket->getCreatedBy()) ? $ticket->getAssignedTo() : $ticket->getCreatedBy();
        if ($recipient && $recipient !== $sender) {
            $this->notificationService->notify(
                $recipient,
                "New Ticket Reply",
                "{$sender->getFullName()} replied to ticket {$ticket->getTicketNumber()}.",
                'message_received',
                $ticket->getId()
            );
        }

        return $message;
    }

    /**
     * Assign or reassign a technician to a ticket.
     */
    public function assignTechnician(Ticket $ticket, User $actor, User $technician): void
    {
        $prevTechName = $ticket->getAssignedTo() ? $ticket->getAssignedTo()->getFullName() : null;
        if ($prevTechName !== $technician->getFullName()) {
            $ticket->setAssignedTo($technician);
            if ($ticket->getStatus() === 'Open') {
                $ticket->setStatus('Assigned');
            }
            $this->em->flush();

            $eventType = $prevTechName ? 'ticket_reassigned' : 'ticket_assigned';
            $this->ticketActivityLogger->logActivity(
                ticket: $ticket,
                actor: $actor,
                eventType: $eventType,
                previousValue: $prevTechName,
                newValue: $technician->getFullName(),
                description: sprintf('Ticket assigned to %s', $technician->getFullName())
            );

            $this->auditLogger->log($actor, 'assign_ticket', 'Ticket', $ticket->getId(), [
                'assignedTo' => $technician->getEmail(),
            ]);

            if ($technician !== $actor) {
                $this->notificationService->notify(
                    $technician,
                    "New Ticket Assigned",
                    "You have been assigned ticket {$ticket->getTicketNumber()}.",
                    'ticket_assigned',
                    $ticket->getId()
                );
            }
        }
    }

    /**
     * Update ticket status and notify creator.
     */
    public function updateStatus(Ticket $ticket, User $actor, string $newStatus): void
    {
        $oldStatus = $ticket->getStatus();
        if ($oldStatus !== $newStatus) {
            if ($newStatus === 'Resolved' || $newStatus === 'Closed') {
                $depRepo = $this->em->getRepository(\App\Entity\TicketDependency::class);
                $blocking = $depRepo->findUnresolvedBlockingDependencies($ticket);
                if (!empty($blocking)) {
                    $blockers = array_map(fn($d) => '#' . $d->getDependsOnTicket()->getTicketNumber(), $blocking);
                    throw new \LogicException(sprintf('Cannot resolve or close ticket because it is blocked by unresolved ticket(s): %s', implode(', ', $blockers)));
                }
            }

            $ticket->setStatus($newStatus);
            $this->em->flush();

            // SLA Resolution and Pause/Resume Integration
            if ($this->slaService) {
                if ($newStatus === 'Resolved' || $newStatus === 'Closed') {
                    $this->slaService->recordResolution($ticket);
                } else {
                    $sla = $this->em->getRepository(\App\Entity\TicketSla::class)->findOneBy(['ticket' => $ticket]);
                    if ($sla) {
                        if (str_contains(strtolower($newStatus), 'waiting')) {
                            $this->slaService->pauseSla($sla);
                        } elseif ($sla->getPausedAt() !== null) {
                            $this->slaService->resumeSla($sla);
                        }
                    }
                }
            }

            $this->ticketActivityLogger->logActivity(
                ticket: $ticket,
                actor: $actor,
                eventType: 'status_changed',
                previousValue: $oldStatus,
                newValue: $newStatus,
                description: sprintf('Status changed from %s to %s', $oldStatus, $newStatus)
            );

            $this->auditLogger->log($actor, 'change_status', 'Ticket', $ticket->getId(), [
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus
            ]);

            if ($ticket->getCreatedBy() && $ticket->getCreatedBy() !== $actor) {
                $this->notificationService->notify(
                    $ticket->getCreatedBy(),
                    "Ticket Status Changed",
                    "Your ticket {$ticket->getTicketNumber()} is now {$newStatus}.",
                    'status_changed',
                    $ticket->getId()
                );
            }
        }
    }

    /**
     * Reopen a resolved ticket with a mandatory reason.
     */
    public function reopenTicket(Ticket $ticket, User $actor, string $reason): void
    {
        $role = $actor->getRoleEntity()?->getName();
        if ($role !== 'ROLE_ADMIN' && $ticket->getCreatedBy() !== $actor) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Only the ticket creator or an administrator can reopen this ticket.');
        }

        if ($ticket->getStatus() !== 'Resolved') {
            throw new \InvalidArgumentException('Only tickets with "Resolved" status can be reopened.');
        }

        if (!trim($reason)) {
            throw new \InvalidArgumentException('A meaningful reason is required to reopen a ticket.');
        }

        $oldStatus = $ticket->getStatus();
        $ticket->setStatus('Reopened');
        $this->em->flush();

        if ($this->slaService) {
            $this->slaService->restartSlaForReopenedTicket($ticket, $actor);
        }

        $this->ticketActivityLogger->logActivity(
            ticket: $ticket,
            actor: $actor,
            eventType: 'ticket_reopened',
            previousValue: $oldStatus,
            newValue: 'Reopened',
            description: sprintf('Ticket reopened by %s. Reason: %s', $actor->getFullName(), $reason),
            metadata: ['reason' => $reason]
        );

        $this->auditLogger->log($actor, 'reopen_ticket', 'Ticket', $ticket->getId(), [
            'reason' => $reason
        ]);

        $recipient = $ticket->getAssignedTo();
        if ($recipient && $recipient !== $actor) {
            $this->notificationService->notify(
                $recipient,
                "Ticket Reopened",
                "Ticket {$ticket->getTicketNumber()} was reopened by employee. Reason: {$reason}",
                'ticket_reopened',
                $ticket->getId()
            );
        }
    }

    /**
     * Submit a CSAT rating for a ticket.
     */
    public function submitCsatRating(Ticket $ticket, User $actor, int $rating, ?string $comment = null): \App\Entity\CsatRating
    {
        $role = $actor->getRoleEntity()?->getName();
        if ($role !== 'ROLE_ADMIN' && $ticket->getCreatedBy() !== $actor) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Only the ticket owner or an admin can rate this ticket.');
        }

        if ($ticket->getStatus() !== 'Closed' && $ticket->getStatus() !== 'Resolved') {
            throw new \InvalidArgumentException('CSAT rating can only be submitted for resolved or closed tickets.');
        }

        $existing = $this->em->getRepository(\App\Entity\CsatRating::class)->findOneBy([
            'ticket' => $ticket,
            'user' => $actor,
        ]);
        if ($existing) {
            throw new \LogicException('A CSAT rating has already been submitted for this ticket.');
        }

        $csat = new \App\Entity\CsatRating();
        $csat->setTicket($ticket);
        $csat->setUser($actor);
        $csat->setRating($rating);
        $csat->setComment($comment);

        $this->em->persist($csat);
        $this->em->flush();

        $this->ticketActivityLogger->logActivity(
            ticket: $ticket,
            actor: $actor,
            eventType: 'csat_submitted',
            previousValue: null,
            newValue: (string) $rating,
            description: sprintf('CSAT rating %d/5 submitted by %s', $rating, $actor->getFullName()),
            metadata: ['rating' => $rating, 'comment' => $comment]
        );

        return $csat;
    }

    /**
     * Perform bulk operations on a list of tickets with authoritative per-ticket RBAC checks.
     */
    public function bulkUpdateTickets(User $actor, array $ticketIds, string $action, mixed $value): array
    {
        $updated = [];
        $failed = [];

        $roleName = $actor->getRoleEntity()?->getName();
        $isStaff = in_array($roleName, ['ROLE_IT_TECH', 'ROLE_MAINTENANCE_TECH', 'ROLE_HR', 'ROLE_ADMIN']);

        foreach ($ticketIds as $ticketId) {
            $ticket = $this->em->getRepository(Ticket::class)->find($ticketId);
            if (!$ticket) {
                $failed[] = ['id' => $ticketId, 'reason' => 'Ticket not found'];
                continue;
            }

            if (!$this->isAuthorizedToAccess($actor, $ticket)) {
                $failed[] = ['id' => $ticketId, 'reason' => 'Access Denied'];
                continue;
            }

            try {
                switch ($action) {
                    case 'assign':
                        if (!$isStaff) {
                            $failed[] = ['id' => $ticketId, 'reason' => 'Only staff members can assign tickets'];
                            break;
                        }
                        $tech = $this->em->getRepository(User::class)->find((int) $value);
                        if (!$tech) {
                            $failed[] = ['id' => $ticketId, 'reason' => 'Technician not found'];
                            break;
                        }
                        $prevTech = $ticket->getAssignedTo() ? $ticket->getAssignedTo()->getFirstName() . ' ' . $ticket->getAssignedTo()->getLastName() : 'Unassigned';
                        $ticket->setAssignedTo($tech);
                        if ($ticket->getStatus() === 'Open') {
                            $ticket->setStatus('Assigned');
                        }
                        $this->ticketActivityLogger->logActivity(
                            ticket: $ticket,
                            actor: $actor,
                            eventType: 'technician_assigned',
                            previousValue: $prevTech,
                            newValue: $tech->getFirstName() . ' ' . $tech->getLastName(),
                            description: sprintf('Bulk assigned to %s', $tech->getFirstName() . ' ' . $tech->getLastName())
                        );
                        $updated[] = $ticketId;
                        break;

                    case 'status':
                        if (!$isStaff) {
                            $failed[] = ['id' => $ticketId, 'reason' => 'Only staff members can modify ticket status'];
                            break;
                        }
                        $prevStatus = $ticket->getStatus();
                        $newStatus = (string) $value;
                        $ticket->setStatus($newStatus);
                        if ($newStatus === 'Closed' && !$ticket->getClosedAt()) {
                            $ticket->setClosedAt(new \DateTimeImmutable());
                        }
                        $this->ticketActivityLogger->logActivity(
                            ticket: $ticket,
                            actor: $actor,
                            eventType: 'status_changed',
                            previousValue: $prevStatus,
                            newValue: $newStatus,
                            description: sprintf('Bulk status updated to %s', $newStatus)
                        );
                        $updated[] = $ticketId;
                        break;

                    case 'priority':
                        if (!$isStaff) {
                            $failed[] = ['id' => $ticketId, 'reason' => 'Only staff members can modify ticket priority'];
                            break;
                        }
                        $prevPriority = $ticket->getPriority();
                        $newPriority = (string) $value;
                        $ticket->setPriority($newPriority);
                        $this->ticketActivityLogger->logActivity(
                            ticket: $ticket,
                            actor: $actor,
                            eventType: 'priority_changed',
                            previousValue: $prevPriority,
                            newValue: $newPriority,
                            description: sprintf('Bulk priority updated to %s', $newPriority)
                        );
                        $updated[] = $ticketId;
                        break;

                    case 'close':
                        $isOwner = $ticket->getCreatedBy() === $actor;
                        if (!$isStaff && !$isOwner) {
                            $failed[] = ['id' => $ticketId, 'reason' => 'Not authorized to close ticket'];
                            break;
                        }
                        $prevStatus = $ticket->getStatus();
                        $ticket->setStatus('Closed');
                        if (!$ticket->getClosedAt()) {
                            $ticket->setClosedAt(new \DateTimeImmutable());
                        }
                        $this->ticketActivityLogger->logActivity(
                            ticket: $ticket,
                            actor: $actor,
                            eventType: 'ticket_closed',
                            previousValue: $prevStatus,
                            newValue: 'Closed',
                            description: 'Bulk closed ticket'
                        );
                        $updated[] = $ticketId;
                        break;

                    default:
                        $failed[] = ['id' => $ticketId, 'reason' => 'Unknown action'];
                        break;
                }
            } catch (\Exception $e) {
                $failed[] = ['id' => $ticketId, 'reason' => $e->getMessage()];
            }
        }

        if (count($updated) > 0) {
            $this->em->flush();
        }

        return [
            'updated' => $updated,
            'failed' => $failed,
        ];
    }
}
