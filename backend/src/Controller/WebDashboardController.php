<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Notification;
use App\Entity\LeaveRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebDashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $role = $user->getRoleEntity()->getName();

        if ($role === 'ROLE_ADMIN') {
            return $this->renderAdminDashboard($em);
        } elseif ($role === 'ROLE_HR') {
            return $this->renderHrDashboard($em);
        } elseif ($role === 'ROLE_IT_TECH' || $role === 'ROLE_MAINTENANCE_TECH') {
            return $this->renderTechnicianDashboard($em, $user);
        } else {
            return $this->renderEmployeeDashboard($em, $user);
        }
    }

    private function renderAdminDashboard(EntityManagerInterface $em): Response
    {
        // 1. Total users
        $totalUsers = $em->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u')->getSingleScalarResult();

        // 2. Total tickets
        $totalTickets = $em->createQuery('SELECT COUNT(t.id) FROM App\Entity\Ticket t')->getSingleScalarResult();

        // 3. Tickets by category
        $categoriesData = $em->createQuery(
            'SELECT t.category, COUNT(t.id) as count FROM App\Entity\Ticket t GROUP BY t.category'
        )->getResult();

        // 5. Avg resolution time in hours
        $closedTickets = $em->createQuery(
            'SELECT t.createdAt, t.closedAt FROM App\Entity\Ticket t WHERE t.closedAt IS NOT NULL'
        )->getResult();
        
        $avgResolutionTime = 0;
        if (count($closedTickets) > 0) {
            $totalHours = 0;
            foreach ($closedTickets as $ticket) {
                /** @var \DateTimeImmutable $created */
                $created = $ticket['createdAt'];
                /** @var \DateTimeImmutable $closed */
                $closed = $ticket['closedAt'];
                $diff = $closed->getTimestamp() - $created->getTimestamp();
                $totalHours += $diff / 3600;
            }
            $avgResolutionTime = round($totalHours / count($closedTickets), 1);
        }

        // 6. Technician workload
        $techWorkloads = $em->createQuery(
            'SELECT u.firstName, u.lastName, COUNT(t.id) as activeTickets 
             FROM App\Entity\Ticket t
             JOIN t.assignedTo u
             WHERE t.status IN (:activeStatuses)
             GROUP BY u.id'
        )
        ->setParameter('activeStatuses', ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician'])
        ->getResult();

        return $this->render('dashboard/admin.html.twig', [
            'total_users' => $totalUsers,
            'total_tickets' => $totalTickets,
            'categories' => $categoriesData,
            'avg_resolution_time' => $avgResolutionTime,
            'workloads' => $techWorkloads,
        ]);
    }

    private function renderHrDashboard(EntityManagerInterface $em): Response
    {
        // 1. Pending leave requests
        $pendingLeave = $em->createQuery(
            'SELECT COUNT(t.id) FROM App\Entity\Ticket t
             WHERE t.category = :category AND t.status = :status'
        )
        ->setParameter('category', 'Leave Request')
        ->setParameter('status', 'Open')
        ->getSingleScalarResult();

        // 2. Approved leave
        $approvedLeave = $em->createQuery(
            'SELECT COUNT(t.id) FROM App\Entity\Ticket t
             WHERE t.category = :category AND t.status = :status'
        )
        ->setParameter('category', 'Leave Request')
        ->setParameter('status', 'Approved')
        ->getSingleScalarResult();

        // 3. Rejected leave
        $rejectedLeave = $em->createQuery(
            'SELECT COUNT(t.id) FROM App\Entity\Ticket t
             WHERE t.category = :category AND t.status = :status'
        )
        ->setParameter('category', 'Leave Request')
        ->setParameter('status', 'Rejected')
        ->getSingleScalarResult();

        // 4. Leave Calendar (recent active and upcoming leave requests)
        $leaveRequests = $em->createQuery(
            'SELECT r, t, u FROM App\Entity\LeaveRequest r
             JOIN r.ticket t
             JOIN t.createdBy u
             ORDER BY r.startDate DESC'
        )->getResult();

        return $this->render('dashboard/hr.html.twig', [
            'pending_leave' => $pendingLeave,
            'approved_leave' => $approvedLeave,
            'rejected_leave' => $rejectedLeave,
            'leave_requests' => $leaveRequests,
        ]);
    }

    private function renderTechnicianDashboard(EntityManagerInterface $em, User $user): Response
    {
        // 1. Assigned tickets
        $assignedTickets = $em->getRepository(Ticket::class)->findBy([
            'assignedTo' => $user,
            'status' => ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician']
        ]);

        // 2. Urgent / Critical tickets (Priority High/Critical)
        $urgentTicketsRaw = $em->getRepository(Ticket::class)->findBy([
            'assignedTo' => $user,
            'priority' => ['High', 'Critical'],
            'status' => ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician']
        ]);

        // 3. SLA Warnings (Tickets assigned to this tech that have been created more than 24 hours ago and are not resolved/closed)
        $warningLimit = new \DateTimeImmutable('-24 hours');
        $slaWarnings = $em->createQuery(
            'SELECT t FROM App\Entity\Ticket t
             WHERE t.assignedTo = :tech
             AND t.status IN (:activeStatuses)
             AND t.createdAt < :limit'
        )
        ->setParameter('tech', $user)
        ->setParameter('activeStatuses', ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician'])
        ->setParameter('limit', $warningLimit)
        ->getResult();

        // Merge SLA warnings into Urgent tickets list (avoiding duplicates)
        $urgentTickets = [];
        $urgentIds = [];
        foreach ($urgentTicketsRaw as $t) {
            $urgentTickets[] = $t;
            $urgentIds[] = $t->getId();
        }
        foreach ($slaWarnings as $t) {
            if (!in_array($t->getId(), $urgentIds)) {
                $urgentTickets[] = $t;
                $urgentIds[] = $t->getId();
            }
        }

        // 4. Performance statistics: total tickets resolved
        $totalResolved = $em->createQuery(
            'SELECT COUNT(t.id) FROM App\Entity\Ticket t
             WHERE t.assignedTo = :tech AND t.status IN (:doneStatuses)'
        )
        ->setParameter('tech', $user)
        ->setParameter('doneStatuses', ['Resolved', 'Closed'])
        ->getSingleScalarResult();

        return $this->render('dashboard/technician.html.twig', [
            'assigned_tickets' => $assignedTickets,
            'urgent_tickets' => $urgentTickets,
            'sla_warnings' => $slaWarnings,
            'total_resolved' => $totalResolved,
        ]);
    }

    private function renderEmployeeDashboard(EntityManagerInterface $em, User $user): Response
    {
        // 1. Open tickets
        $openTickets = $em->getRepository(Ticket::class)->findBy([
            'createdBy' => $user,
            'status' => ['Open', 'Assigned']
        ]);

        // 2. In progress tickets
        $inProgressTickets = $em->getRepository(Ticket::class)->findBy([
            'createdBy' => $user,
            'status' => 'In Progress'
        ]);

        // 3. Resolved tickets
        $resolvedTickets = $em->getRepository(Ticket::class)->findBy([
            'createdBy' => $user,
            'status' => ['Resolved', 'Closed']
        ]);

        // 4. Recent notifications
        $notifications = $em->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('dashboard/employee.html.twig', [
            'open_tickets' => $openTickets,
            'inprogress_tickets' => $inProgressTickets,
            'resolved_tickets' => $resolvedTickets,
            'notifications' => $notifications,
        ]);
    }
}
