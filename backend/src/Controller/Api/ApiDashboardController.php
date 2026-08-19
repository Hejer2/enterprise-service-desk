<?php

namespace App\Controller\Api;

use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ApiDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $role = $user->getRoleEntity()?->getName();

        // Fetch notifications for all user roles
        $notificationsRaw = $em->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            15
        );

        $notifications = [];
        foreach ($notificationsRaw as $notif) {
            $notifications[] = [
                'id' => $notif->getId(),
                'title' => $notif->getTitle() ?? 'Notification',
                'message' => $notif->getContent(),
                'type' => $notif->getType() ?? 'ticket',
                'relatedId' => $notif->getRelatedId(),
                'isRead' => $notif->isRead(),
                'createdAt' => $notif->getCreatedAt()->format('d M Y, H:i'),
            ];
        }
        
        if ($role === 'ROLE_HR') {
            $pendingLeave = $em->createQuery(
                'SELECT COUNT(t.id) FROM App\Entity\Ticket t
                 WHERE t.category = :category AND t.status = :status'
            )
            ->setParameter('category', 'Leave Request')
            ->setParameter('status', 'Open')
            ->getSingleScalarResult();

            $approvedLeave = $em->createQuery(
                'SELECT COUNT(t.id) FROM App\Entity\Ticket t
                 WHERE t.category = :category AND t.status = :status'
            )
            ->setParameter('category', 'Leave Request')
            ->setParameter('status', 'Approved')
            ->getSingleScalarResult();

            $rejectedLeave = $em->createQuery(
                'SELECT COUNT(t.id) FROM App\Entity\Ticket t
                 WHERE t.category = :category AND t.status = :status'
            )
            ->setParameter('category', 'Leave Request')
            ->setParameter('status', 'Rejected')
            ->getSingleScalarResult();

            $leaveRequestsRaw = $em->createQuery(
                'SELECT r, t, u FROM App\Entity\LeaveRequest r
                 JOIN r.ticket t
                 JOIN t.createdBy u
                 ORDER BY r.startDate DESC'
            )->getResult();

            $leaveRequests = [];
            foreach ($leaveRequestsRaw as $req) {
                $halfDay = $req->isHalfDay();
                $duration = 'Half Day';
                if (!$halfDay) {
                    $diff = $req->getStartDate()->diff($req->getEndDate());
                    $days = $diff->days + 1;
                    $duration = $days . ' Day(s)';
                }

                $leaveRequests[] = [
                    'id' => $req->getId(),
                    'type' => $req->getLeaveType()->getName(),
                    'startDate' => $req->getStartDate()->format('d M Y'),
                    'endDate' => $req->getEndDate()->format('d M Y'),
                    'duration' => $duration,
                    'employeeName' => $req->getTicket()->getCreatedBy()->getFullName(),
                    'ticketId' => $req->getTicket()->getId(),
                    'ticketNumber' => $req->getTicket()->getTicketNumber(),
                    'status' => $req->getTicket()->getStatus(),
                ];
            }

            return $this->json([
                'type' => 'hr',
                'pendingLeave' => (int) $pendingLeave,
                'approvedLeave' => (int) $approvedLeave,
                'rejectedLeave' => (int) $rejectedLeave,
                'leaveRequests' => $leaveRequests,
                'notifications' => $notifications,
            ]);
        } elseif ($role === 'ROLE_IT_TECH' || $role === 'ROLE_MAINTENANCE_TECH') {
            $roleType = $role === 'ROLE_IT_TECH' ? 'it' : 'maintenance';
            // Technician Dashboard
            $assignedTickets = $em->getRepository(Ticket::class)->findBy([
                'assignedTo' => $user,
                'status' => ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician']
            ]);

            $urgentTicketsRaw = $em->getRepository(Ticket::class)->findBy([
                'assignedTo' => $user,
                'priority' => ['High', 'Critical'],
                'status' => ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician']
            ]);

            $warningLimit = new \DateTimeImmutable('-24 hours');
            $slaWarningsRaw = $em->createQuery(
                'SELECT t FROM App\Entity\Ticket t
                 WHERE t.assignedTo = :tech
                 AND t.status IN (:activeStatuses)
                 AND t.createdAt < :limit'
            )
            ->setParameter('tech', $user)
            ->setParameter('activeStatuses', ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician'])
            ->setParameter('limit', $warningLimit)
            ->getResult();
            
            $urgentTickets = [];
            $urgentIds = [];
            foreach ($urgentTicketsRaw as $t) {
                $urgentTickets[] = $t;
                $urgentIds[] = $t->getId();
            }
            foreach ($slaWarningsRaw as $t) {
                if (!in_array($t->getId(), $urgentIds)) {
                    $urgentTickets[] = $t;
                    $urgentIds[] = $t->getId();
                }
            }

            $totalResolved = $em->createQuery(
                'SELECT COUNT(t.id) FROM App\Entity\Ticket t
                 WHERE t.assignedTo = :tech AND t.status IN (:doneStatuses)'
            )
            ->setParameter('tech', $user)
            ->setParameter('doneStatuses', ['Resolved', 'Closed'])
            ->getSingleScalarResult();

            $slaWarnings = [];
            foreach ($slaWarningsRaw as $tw) {
                $slaWarnings[] = [
                    'id' => $tw->getId(),
                    'ticketNumber' => $tw->getTicketNumber(),
                    'title' => $tw->getTitle(),
                    'priority' => $tw->getPriority(),
                    'createdAt' => $tw->getCreatedAt()->format('d M, H:i'),
                ];
            }
            
            $assignedTicketsList = [];
            foreach ($assignedTickets as $at) {
                $assignedTicketsList[] = [
                    'id' => $at->getId(),
                    'ticketNumber' => $at->getTicketNumber(),
                    'title' => $at->getTitle(),
                    'status' => $at->getStatus(),
                    'priority' => $at->getPriority(),
                ];
            }
            
            $urgentTicketsList = [];
            foreach ($urgentTickets as $ut) {
                $urgentTicketsList[] = [
                    'id' => $ut->getId(),
                    'ticketNumber' => $ut->getTicketNumber(),
                    'title' => $ut->getTitle(),
                    'status' => $ut->getStatus(),
                    'priority' => $ut->getPriority(),
                ];
            }

            return $this->json([
                'type' => $roleType,
                'assignedTickets' => count($assignedTickets),
                'urgentTickets' => count($urgentTickets),
                'slaWarnings' => $slaWarnings,
                'assignedTicketsList' => $assignedTicketsList,
                'urgentTicketsList' => $urgentTicketsList,
                'totalResolved' => (int) $totalResolved,
                'notifications' => $notifications,
            ]);
        } elseif ($role === 'ROLE_ADMIN') {
            // 1. Total users
            $totalUsers = $em->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u')->getSingleScalarResult();

            // 2. Total tickets
            $totalTickets = $em->createQuery('SELECT COUNT(t.id) FROM App\Entity\Ticket t')->getSingleScalarResult();

            // 3. Avg resolution time in hours
            $closedTickets = $em->createQuery(
                'SELECT t.createdAt, t.closedAt FROM App\Entity\Ticket t WHERE t.closedAt IS NOT NULL'
            )->getResult();
            
            $avgResolutionTime = 0;
            if (count($closedTickets) > 0) {
                $totalHours = 0;
                foreach ($closedTickets as $ticket) {
                    $created = $ticket['createdAt'];
                    $closed = $ticket['closedAt'];
                    $diff = $closed->getTimestamp() - $created->getTimestamp();
                    $totalHours += $diff / 3600;
                }
                $avgResolutionTime = round($totalHours / count($closedTickets), 1);
            }

            // 4. Tickets by category
            $categoriesData = $em->createQuery(
                'SELECT t.category as name, COUNT(t.id) as count FROM App\Entity\Ticket t GROUP BY t.category'
            )->getResult();

            foreach ($categoriesData as &$cat) {
                $cat['pct'] = $totalTickets > 0 ? round(($cat['count'] / $totalTickets) * 100) : 0;
            }

            // 5. Technician workload
            $techWorkloadsRaw = $em->createQuery(
                'SELECT u.firstName, u.lastName, COUNT(t.id) as activeTickets 
                 FROM App\Entity\Ticket t
                 JOIN t.assignedTo u
                 WHERE t.status IN (:activeStatuses)
                 GROUP BY u.id'
            )
            ->setParameter('activeStatuses', ['Assigned', 'In Progress', 'Waiting for Employee', 'Waiting for Technician'])
            ->getResult();

            $techWorkloads = [];
            foreach ($techWorkloadsRaw as $workload) {
                $techWorkloads[] = [
                    'name' => $workload['firstName'] . ' ' . $workload['lastName'],
                    'active' => (int) $workload['activeTickets'],
                ];
            }

            return $this->json([
                'type' => 'admin',
                'totalUsers' => (int) $totalUsers,
                'totalTickets' => (int) $totalTickets,
                'avgResolutionTime' => $avgResolutionTime,
                'categories' => $categoriesData,
                'workloads' => $techWorkloads,
                'notifications' => $notifications,
            ]);
        } else {
            // Employee Dashboard
            $openTickets = $em->getRepository(Ticket::class)->findBy([
                'createdBy' => $user,
                'status' => ['Open', 'Assigned']
            ]);

            $inProgressTickets = $em->getRepository(Ticket::class)->findBy([
                'createdBy' => $user,
                'status' => 'In Progress'
            ]);

            $resolvedTickets = $em->getRepository(Ticket::class)->findBy([
                'createdBy' => $user,
                'status' => ['Resolved', 'Closed']
            ]);

            $activeTicketsRaw = $em->getRepository(Ticket::class)->findBy([
                'createdBy' => $user,
                'status' => ['Open', 'Assigned', 'In Progress', 'Waiting for Technician', 'Waiting for Employee']
            ], ['createdAt' => 'DESC'], 5);

            $activeTicketsList = [];
            foreach ($activeTicketsRaw as $t) {
                $activeTicketsList[] = [
                    'id' => $t->getId(),
                    'ticketNumber' => $t->getTicketNumber(),
                    'title' => $t->getTitle(),
                    'status' => $t->getStatus(),
                    'priority' => $t->getPriority(),
                ];
            }

            return $this->json([
                'type' => 'employee',
                'openTickets' => count($openTickets),
                'inProgressTickets' => count($inProgressTickets),
                'resolvedTickets' => count($resolvedTickets),
                'notifications' => $notifications,
                'activeTicketsList' => $activeTicketsList,
            ]);
        }
    }
}
