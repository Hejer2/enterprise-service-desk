<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class TicketRouter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private TicketActivityLogger $activityLogger
    ) {}

    /**
     * Auto-routes a ticket to a technician based on category.
     * Uses workload balancing (assigns to the technician with the least active tickets).
     */
    public function routeTicket(Ticket $ticket): void
    {
        $category = $ticket->getCategory();
        $targetRole = null;

        if ($category === 'IT Support' || $category === 'Incident') {
            $targetRole = 'ROLE_IT_TECH';
        } elseif ($category === 'Machine Maintenance') {
            $targetRole = 'ROLE_MAINTENANCE_TECH';
        } elseif ($category === 'Leave Request') {
            $targetRole = 'ROLE_HR';
        }

        if (!$targetRole) {
            // General Request, Suggestion etc., leave unassigned for Admins to manual routing
            $ticket->setStatus('Open');
            return;
        }

        // Find all users with the target role
        $technicians = $this->entityManager->createQuery(
            'SELECT u FROM App\Entity\User u
             JOIN u.roleEntity r
             WHERE r.name = :role'
        )
        ->setParameter('role', $targetRole)
        ->getResult();

        if (empty($technicians)) {
            $ticket->setStatus('Open');
            return;
        }

        // Find the technician with the least open/in-progress tickets
        $bestTech = null;
        $minWorkload = null;

        foreach ($technicians as $tech) {
            $workload = (int) $this->entityManager->createQuery(
                'SELECT COUNT(t.id) FROM App\Entity\Ticket t
                 WHERE t.assignedTo = :tech
                 AND t.status IN (:activeStatuses)'
            )
            ->setParameter('tech', $tech)
            ->setParameter('activeStatuses', ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician'])
            ->getSingleScalarResult();

            if ($minWorkload === null || $workload < $minWorkload) {
                $minWorkload = $workload;
                $bestTech = $tech;
            }
        }

        if ($bestTech) {
            $ticket->setAssignedTo($bestTech);
            $ticket->setStatus('Assigned');
            $this->activityLogger->logActivity(
                ticket: $ticket,
                actor: null,
                eventType: 'ticket_assigned',
                previousValue: null,
                newValue: $bestTech->getFullName(),
                description: sprintf('Automated routing assigned ticket to %s based on workload', $bestTech->getFullName())
            );
        } else {
            $ticket->setStatus('Open');
        }
    }
}
