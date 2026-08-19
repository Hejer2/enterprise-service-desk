<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\CsatRating;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ReportService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Build comprehensive report statistics based on date range and active user's RBAC scope.
     */
    public function getReportData(?User $user, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null, ?string $category = null, ?int $techId = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->orderBy('t.createdAt', 'DESC');

        if ($startDate) {
            $qb->andWhere('t.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('t.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        if ($category) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }

        if ($techId) {
            $qb->andWhere('t.assignedTo = :techId')
               ->setParameter('techId', $techId);
        }

        // RBAC Filter: Technicians only see tickets assigned to them or in their domain if restricted
        if ($user && $user->getRoleEntity()) {
            $roleName = $user->getRoleEntity()->getName();
            if (in_array($roleName, ['ROLE_IT_TECH', 'ROLE_MAINTENANCE_TECH'])) {
                $qb->andWhere('t.assignedTo = :currentUser OR t.createdBy = :currentUser')
                   ->setParameter('currentUser', $user);
            }
        }

        /** @var Ticket[] $tickets */
        $tickets = $qb->getQuery()->getResult();

        $byStatus = [];
        $byPriority = [];
        $byCategory = [];
        $byTechnician = [];
        $byDepartment = [];
        $monthlyTrend = [];

        $resolvedCount = 0;
        $totalResolutionHours = 0;
        $openCount = 0;
        $closedCount = 0;

        foreach ($tickets as $ticket) {
            $status = $ticket->getStatus();
            $priority = $ticket->getPriority() ?: 'Medium';
            $cat = $ticket->getCategory() ?: 'General';
            $techName = $ticket->getAssignedTo() ? $ticket->getAssignedTo()->getFirstName() . ' ' . $ticket->getAssignedTo()->getLastName() : 'Unassigned';

            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
            $byPriority[$priority] = ($byPriority[$priority] ?? 0) + 1;
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;
            $byTechnician[$techName] = ($byTechnician[$techName] ?? 0) + 1;
            $byDepartment[$cat] = ($byDepartment[$cat] ?? 0) + 1;

            $monthKey = $ticket->getCreatedAt()->format('Y-m');
            $monthlyTrend[$monthKey] = ($monthlyTrend[$monthKey] ?? 0) + 1;

            if (in_array($status, ['Resolved', 'Closed'])) {
                $closedCount++;
                $resolvedCount++;
                if ($ticket->getClosedAt()) {
                    $diff = $ticket->getClosedAt()->getTimestamp() - $ticket->getCreatedAt()->getTimestamp();
                    $totalResolutionHours += ($diff / 3600);
                }
            } else {
                $openCount++;
            }
        }

        $avgResolutionTime = $resolvedCount > 0 ? round($totalResolutionHours / $resolvedCount, 1) : 0;

        // CSAT Statistics
        $csatQb = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(CsatRating::class, 'c');

        if ($startDate && $endDate) {
            $csatQb->andWhere('c.createdAt >= :startDate AND c.createdAt <= :endDate')
                   ->setParameter('startDate', $startDate)
                   ->setParameter('endDate', $endDate);
        }

        /** @var CsatRating[] $csatRatings */
        $csatRatings = $csatQb->getQuery()->getResult();

        $csatSum = 0;
        $csatCount = count($csatRatings);
        $csatDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($csatRatings as $rating) {
            $val = $rating->getRating();
            $csatSum += $val;
            if (isset($csatDistribution[$val])) {
                $csatDistribution[$val]++;
            }
        }

        $csatAverage = $csatCount > 0 ? round($csatSum / $csatCount, 1) : 0.0;

        // SLA Metrics Calculation
        $slaQb = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(\App\Entity\TicketSla::class, 's');

        if (!empty($tickets)) {
            $ticketIds = array_map(fn($t) => $t->getId(), $tickets);
            $slaQb->andWhere('s.ticket IN (:tIds)')->setParameter('tIds', $ticketIds);
        } else {
            $slaQb->andWhere('1=0');
        }

        /** @var \App\Entity\TicketSla[] $slas */
        $slas = $slaQb->getQuery()->getResult();

        $totalSlas = count($slas);
        $slaBreachedCount = 0;
        $slaAtRiskCount = 0;
        $slaCompletedCount = 0;
        $firstResponseTimes = [];
        $resolutionTimes = [];

        foreach ($slas as $s) {
            if ($s->getResolutionStatus() === 'BREACHED') {
                $slaBreachedCount++;
            } elseif ($s->getResolutionStatus() === 'AT_RISK') {
                $slaAtRiskCount++;
            } elseif ($s->getResolutionStatus() === 'COMPLETED') {
                $slaCompletedCount++;
            }

            if ($s->getFirstResponseCompletedAt() && $s->getCreatedAt()) {
                $diffMins = ($s->getFirstResponseCompletedAt()->getTimestamp() - $s->getCreatedAt()->getTimestamp()) / 60;
                $firstResponseTimes[] = max(0, $diffMins);
            }

            if ($s->getResolutionCompletedAt() && $s->getCreatedAt()) {
                $diffHours = ($s->getResolutionCompletedAt()->getTimestamp() - $s->getCreatedAt()->getTimestamp()) / 3600;
                $resolutionTimes[] = max(0, $diffHours);
            }
        }

        $slaCompliance = $totalSlas > 0 ? round((($totalSlas - $slaBreachedCount) / $totalSlas) * 100, 1) : 100.0;
        $avgFirstResponseMins = count($firstResponseTimes) > 0 ? round(array_sum($firstResponseTimes) / count($firstResponseTimes), 0) : 0;
        $avgSlaResolutionHours = count($resolutionTimes) > 0 ? round(array_sum($resolutionTimes) / count($resolutionTimes), 1) : $avgResolutionTime;

        ksort($monthlyTrend);

        return [
            'totalTickets' => count($tickets),
            'openCount' => $openCount,
            'closedCount' => $closedCount,
            'avgResolutionTime' => $avgResolutionTime,
            'csatAverage' => $csatAverage,
            'csatCount' => $csatCount,
            'csatDistribution' => $csatDistribution,
            'slaCompliance' => $slaCompliance,
            'slaBreaches' => $slaBreachedCount,
            'slaAtRisk' => $slaAtRiskCount,
            'avgFirstResponseMins' => $avgFirstResponseMins,
            'avgSlaResolutionHours' => $avgSlaResolutionHours,
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'byCategory' => $byCategory,
            'byTechnician' => $byTechnician,
            'byDepartment' => $byDepartment,
            'monthlyTrend' => $monthlyTrend,
            'tickets' => $tickets,
        ];
    }

    /**
     * Resolve date range preset to start & end DateTime objects.
     */
    public function resolveDatePreset(?string $preset, ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = new \DateTimeImmutable('now');
        switch ($preset) {
            case 'today':
                return [
                    $now->setTime(0, 0, 0),
                    $now->setTime(23, 59, 59),
                ];
            case 'last_7_days':
                return [
                    $now->modify('-7 days')->setTime(0, 0, 0),
                    $now->setTime(23, 59, 59),
                ];
            case 'this_month':
                return [
                    $now->modify('first day of this month')->setTime(0, 0, 0),
                    $now->setTime(23, 59, 59),
                ];
            case 'previous_month':
                return [
                    $now->modify('first day of last month')->setTime(0, 0, 0),
                    $now->modify('last day of last month')->setTime(23, 59, 59),
                ];
            case 'custom':
                if ($customStart && $customEnd) {
                    return [
                        new \DateTimeImmutable($customStart . ' 00:00:00'),
                        new \DateTimeImmutable($customEnd . ' 23:59:59'),
                    ];
                }
                // Fallthrough to last 30 days
            case 'last_30_days':
            default:
                return [
                    $now->modify('-30 days')->setTime(0, 0, 0),
                    $now->setTime(23, 59, 59),
                ];
        }
    }
}
