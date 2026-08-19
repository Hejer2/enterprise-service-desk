<?php

namespace App\Service;

use App\Entity\RecurringTicket;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\RecurringTicketRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecurringTicketService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecurringTicketRepository $recurringRepository,
        private TicketManagerService $ticketManager,
        private TicketActivityLogger $activityLogger
    ) {}

    public function processDueRecurringTickets(): array
    {
        $dueTickets = $this->recurringRepository->findDueRecurringTickets();
        $createdTickets = [];

        foreach ($dueTickets as $rec) {
            $ticket = new Ticket();
            $ticket->setTicketNumber('TCK-REC-' . strtoupper(substr(uniqid(), -6)));
            $ticket->setTitle($rec->getTitle() . ' (Scheduled Maintenance)');
            $ticket->setDescription($rec->getDescription());
            $ticket->setCategory($rec->getCategory());
            $ticket->setPriority($rec->getPriority());
            $ticket->setStatus('Open');
            $ticket->setCreatedBy($rec->getCreatedBy());
            $ticket->setAssignedTo($rec->getAssignedTo());

            $this->ticketManager->createTicket($ticket);

            $this->activityLogger->logActivity($ticket, null, 'recurring_ticket_created', "Automatically generated from recurring maintenance rule #{$rec->getId()}", 'system', [
                'recurringId' => $rec->getId(),
                'frequency' => $rec->getFrequency(),
            ]);

            // Calculate next execution run
            $rec->setNextRunAt($this->calculateNextRun($rec->getNextRunAt(), $rec->getFrequency()));
            $createdTickets[] = $ticket;
        }

        $this->entityManager->flush();

        return [
            'processed' => count($dueTickets),
            'created' => count($createdTickets),
        ];
    }

    public function calculateNextRun(\DateTimeImmutable $current, string $frequency): \DateTimeImmutable
    {
        return match (strtoupper($frequency)) {
            'DAILY' => $current->modify('+1 day'),
            'WEEKLY' => $current->modify('+1 week'),
            'QUARTERLY' => $current->modify('+3 months'),
            'MONTHLY' => $current->modify('+1 month'),
            default => $current->modify('+1 month'),
        };
    }
}
