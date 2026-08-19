<?php

namespace App\Service;

use App\Entity\ApprovalRequest;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\ApprovalRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApprovalService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApprovalRequestRepository $approvalRepository,
        private NotificationService $notificationService,
        private TicketActivityLogger $activityLogger
    ) {}

    public function createApprovalRequest(Ticket $ticket, User $requester, ?User $approver = null, ?string $reason = null): ApprovalRequest
    {
        $request = new ApprovalRequest();
        $request->setTicket($ticket);
        $request->setRequestedBy($requester);
        $request->setApprover($approver);
        $request->setReason($reason);
        $request->setStatus('PENDING');

        $this->entityManager->persist($request);

        // Update ticket status to Waiting for Approval
        $ticket->setStatus('Waiting for Approval');

        $this->activityLogger->logActivity(
            ticket: $ticket,
            actor: $requester,
            eventType: 'approval_requested',
            previousValue: null,
            newValue: null,
            description: 'Approval requested: ' . ($reason ?: 'Standard operational approval'),
            metadata: [
                'approver' => $approver ? $approver->getFullName() : 'Manager/Admin',
            ]
        );

        if ($approver) {
            $this->notificationService->notify($approver, 'Approval Required', "Ticket #{$ticket->getTicketNumber()} requires your approval.", 'ticket_approval', $ticket->getId());
        }

        $this->entityManager->flush();

        return $request;
    }

    public function respondApproval(ApprovalRequest $approval, User $actor, string $action, ?string $comment = null): void
    {
        $status = strtoupper($action) === 'APPROVE' ? 'APPROVED' : 'REJECTED';
        $approval->setStatus($status);
        $approval->setApprover($actor);
        $approval->setComment($comment);

        $ticket = $approval->getTicket();

        if ($status === 'APPROVED') {
            $ticket->setStatus('In Progress');
            $this->activityLogger->logActivity($ticket, $actor, 'approval_approved', "Approval request APPROVED by {$actor->getFullName()}." . ($comment ? " Comment: $comment" : ''), 'user');
            $this->notificationService->notify($approval->getRequestedBy(), 'Approval Approved', "Your approval request for Ticket #{$ticket->getTicketNumber()} was APPROVED.", 'system', $ticket->getId());
        } else {
            $ticket->setStatus('Rejected');
            $this->activityLogger->logActivity($ticket, $actor, 'approval_rejected', "Approval request REJECTED by {$actor->getFullName()}." . ($comment ? " Comment: $comment" : ''), 'user');
            $this->notificationService->notify($approval->getRequestedBy(), 'Approval Rejected', "Your approval request for Ticket #{$ticket->getTicketNumber()} was REJECTED.", 'system', $ticket->getId());
        }

        $this->entityManager->flush();
    }
}
