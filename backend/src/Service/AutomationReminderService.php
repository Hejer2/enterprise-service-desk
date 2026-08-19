<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AutomationReminderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
        private TicketActivityLogger $activityLogger
    ) {}

    public function processReminders(): array
    {
        $now = new \DateTimeImmutable();
        $thirtyMinsAgo = $now->modify('-30 minutes');
        $sixtyMinsAgo = $now->modify('-60 minutes');

        // 1. Unassigned tickets > 30 mins
        $unassignedTickets = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->where('t.assignedTo IS NULL')
            ->andWhere('t.status = :status')
            ->andWhere('t.createdAt <= :limit')
            ->setParameter('status', 'Open')
            ->setParameter('limit', $thirtyMinsAgo)
            ->getQuery()
            ->getResult();

        $remindersSent = 0;
        $techs = $this->userRepository->findByRoleName('ROLE_IT_TECH');

        foreach ($unassignedTickets as $ticket) {
            foreach ($techs as $tech) {
                $this->notificationService->notify($tech, 'Unassigned Ticket Alert', "Ticket #{$ticket->getTicketNumber()} has been unassigned for 30+ minutes.", 'system', $ticket->getId());
            }
            $remindersSent++;
        }

        return [
            'unassignedCount' => count($unassignedTickets),
            'remindersSent' => $remindersSent,
        ];
    }
}
