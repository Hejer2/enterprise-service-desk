<?php

namespace App\Service;

use App\Entity\AutomationExecution;
use App\Entity\Ticket;
use App\Entity\TicketActivity;
use App\Entity\CsatRating;
use App\Entity\TicketSla;
use App\Entity\User;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ExecutiveAnalyticsService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TicketRepository $ticketRepository,
        private UserRepository $userRepository
    ) {}

    public function getAnalyticsData(array $filters = [], bool $compare = true): array
    {
        $dateRange = $this->resolveDateRange($filters['preset'] ?? '30_days', $filters['from'] ?? null, $filters['to'] ?? null);
        $currentFrom = $dateRange['from'];
        $currentTo = $dateRange['to'];

        $currentData = $this->calculateMetricsForPeriod($currentFrom, $currentTo, $filters);

        $comparison = [];
        if ($compare) {
            $durationSec = $currentTo->getTimestamp() - $currentFrom->getTimestamp();
            $prevTo = clone $currentFrom;
            $prevFrom = (clone $currentFrom)->modify("-{$durationSec} seconds");

            $previousData = $this->calculateMetricsForPeriod($prevFrom, $prevTo, $filters);
            $comparison = $this->calculateComparison($currentData['kpis'], $previousData['kpis']);
        }

        return [
            'period' => [
                'from' => $currentFrom->format('Y-m-d H:i:s'),
                'to' => $currentTo->format('Y-m-d H:i:s'),
                'preset' => $dateRange['preset'],
            ],
            'kpis' => $currentData['kpis'],
            'comparison' => $comparison,
            'ticketTrends' => $currentData['ticketTrends'],
            'statusDistribution' => $currentData['statusDistribution'],
            'priorityDistribution' => $currentData['priorityDistribution'],
            'categoryDistribution' => $currentData['categoryDistribution'],
            'sla' => $currentData['sla'],
            'csat' => $currentData['csat'],
            'technicians' => $currentData['technicians'],
            'automation' => $currentData['automation'],
            'escalations' => $currentData['escalations'],
        ];
    }

    private function resolveDateRange(string $preset, ?string $customFrom, ?string $customTo): array
    {
        $now = new \DateTimeImmutable();
        switch ($preset) {
            case 'today':
                $from = $now->setTime(0, 0, 0);
                $to = $now->setTime(23, 59, 59);
                break;
            case 'yesterday':
                $from = $now->modify('-1 day')->setTime(0, 0, 0);
                $to = $now->modify('-1 day')->setTime(23, 59, 59);
                break;
            case '7_days':
                $from = $now->modify('-7 days')->setTime(0, 0, 0);
                $to = $now->setTime(23, 59, 59);
                break;
            case 'this_month':
                $from = $now->modify('first day of this month')->setTime(0, 0, 0);
                $to = $now->setTime(23, 59, 59);
                break;
            case 'custom':
                $from = $customFrom ? new \DateTimeImmutable($customFrom) : $now->modify('-30 days');
                $to = $customTo ? new \DateTimeImmutable($customTo) : $now;
                break;
            case '30_days':
            default:
                $from = $now->modify('-30 days')->setTime(0, 0, 0);
                $to = $now->setTime(23, 59, 59);
                break;
        }

        return ['from' => $from, 'to' => $to, 'preset' => $preset];
    }

    private function calculateMetricsForPeriod(\DateTimeImmutable $from, \DateTimeImmutable $to, array $filters): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->where('t.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if (!empty($filters['category'])) {
            $qb->andWhere('t.category = :category')->setParameter('category', $filters['category']);
        }
        if (!empty($filters['priority'])) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $filters['priority']);
        }
        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']);
        }
        if (!empty($filters['technician'])) {
            $qb->andWhere('t.assignedTo = :tech')->setParameter('tech', $filters['technician']);
        }

        /** @var Ticket[] $tickets */
        $tickets = $qb->getQuery()->getResult();

        $totalTickets = count($tickets);
        $openTickets = 0;
        $resolvedTickets = 0;
        $closedTickets = 0;

        $statusCounts = [];
        $priorityCounts = [];
        $categoryCounts = [];

        foreach ($tickets as $t) {
            $st = $t->getStatus();
            $pr = $t->getPriority();
            $cat = $t->getCategory();

            $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;
            $priorityCounts[$pr] = ($priorityCounts[$pr] ?? 0) + 1;
            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;

            if ($st === 'Open' || str_contains(strtolower($st), 'progress') || str_contains(strtolower($st), 'waiting')) {
                $openTickets++;
            } elseif ($st === 'Resolved') {
                $resolvedTickets++;
            } elseif ($st === 'Closed') {
                $closedTickets++;
            }
        }

        // SLA Metrics
        $slaTracked = $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(TicketSla::class, 's')
            ->join('s.ticket', 't')
            ->where('t.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getSingleScalarResult();

        $slaBreached = (int)$this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(TicketSla::class, 's')
            ->join('s.ticket', 't')
            ->where('t.createdAt BETWEEN :from AND :to')
            ->andWhere('s.breachedAt IS NOT NULL OR s.resolutionStatus = :breachedStatus')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('breachedStatus', 'BREACHED')
            ->getQuery()->getSingleScalarResult();

        $slaAtRisk = (int)$this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(TicketSla::class, 's')
            ->join('s.ticket', 't')
            ->where('t.createdAt BETWEEN :from AND :to')
            ->andWhere('s.breachedAt IS NULL AND s.resolutionStatus = :atRiskStatus')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('atRiskStatus', 'AT_RISK')
            ->getQuery()->getSingleScalarResult();

        $slaCompliancePct = $slaTracked > 0 ? round((($slaTracked - $slaBreached) / $slaTracked) * 100, 1) : 100.0;

        // CSAT Metrics
        $csatStats = $this->em->createQueryBuilder()
            ->select('AVG(c.rating) as avgRating, COUNT(c.id) as totalRatings')
            ->from(CsatRating::class, 'c')
            ->join('c.ticket', 't')
            ->where('t.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getSingleResult();

        $avgCsat = $csatStats['avgRating'] ? round((float)$csatStats['avgRating'], 2) : 0.0;
        $totalCsatRatings = (int)$csatStats['totalRatings'];

        // Automation Executions
        $autoCount = (int)$this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(AutomationExecution::class, 'a')
            ->where('a.executedAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getSingleScalarResult();

        $autoSuccess = (int)$this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(AutomationExecution::class, 'a')
            ->where('a.executedAt BETWEEN :from AND :to')
            ->andWhere('a.status = :status')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('status', 'SUCCESS')
            ->getQuery()->getSingleScalarResult();

        // Technician Leaderboard
        $techs = $this->userRepository->findByRoleName('ROLE_IT_TECH');
        $techPerformance = [];

        foreach ($techs as $tech) {
            $assignedCount = (int)$this->em->createQueryBuilder()
                ->select('COUNT(t.id)')
                ->from(Ticket::class, 't')
                ->where('t.assignedTo = :tech')
                ->andWhere('t.createdAt BETWEEN :from AND :to')
                ->setParameter('tech', $tech)
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->getQuery()->getSingleScalarResult();

            $resolvedCount = (int)$this->em->createQueryBuilder()
                ->select('COUNT(t.id)')
                ->from(Ticket::class, 't')
                ->where('t.assignedTo = :tech')
                ->andWhere('t.status IN (:closed)')
                ->andWhere('t.createdAt BETWEEN :from AND :to')
                ->setParameter('tech', $tech)
                ->setParameter('closed', ['Resolved', 'Closed'])
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->getQuery()->getSingleScalarResult();

            if ($assignedCount > 0) {
                $techPerformance[] = [
                    'id' => $tech->getId(),
                    'name' => $tech->getFullName(),
                    'assigned' => $assignedCount,
                    'resolved' => $resolvedCount,
                    'completionRate' => round(($resolvedCount / $assignedCount) * 100, 1),
                ];
            }
        }

        // Escalations
        $reopenedCount = (int)$this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.status = :status')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('status', 'Reopened')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getSingleScalarResult();

        return [
            'kpis' => [
                'totalTickets' => $totalTickets,
                'openTickets' => $openTickets,
                'resolvedTickets' => $resolvedTickets,
                'closedTickets' => $closedTickets,
                'slaCompliancePct' => $slaCompliancePct,
                'slaBreaches' => $slaBreached,
                'avgCsat' => $avgCsat,
                'csatRatingsCount' => $totalCsatRatings,
                'automationExecutions' => $autoCount,
            ],
            'statusDistribution' => $statusCounts,
            'priorityDistribution' => $priorityCounts,
            'categoryDistribution' => $categoryCounts,
            'ticketTrends' => [
                ['label' => 'Total Created', 'value' => $totalTickets],
                ['label' => 'Resolved', 'value' => $resolvedTickets],
                ['label' => 'Closed', 'value' => $closedTickets],
            ],
            'sla' => [
                'tracked' => $slaTracked,
                'breached' => $slaBreached,
                'atRisk' => $slaAtRisk,
                'compliancePct' => $slaCompliancePct,
            ],
            'csat' => [
                'avgRating' => $avgCsat,
                'totalRatings' => $totalCsatRatings,
            ],
            'technicians' => $techPerformance,
            'automation' => [
                'total' => $autoCount,
                'success' => $autoSuccess,
                'failed' => $autoCount - $autoSuccess,
            ],
            'escalations' => [
                'slaBreaches' => $slaBreached,
                'reopened' => $reopenedCount,
            ],
        ];
    }

    private function calculateComparison(array $current, array $previous): array
    {
        $comp = [];
        foreach ($current as $key => $currVal) {
            $prevVal = $previous[$key] ?? 0;
            $diff = $currVal - $prevVal;
            $pctChange = $prevVal > 0 ? round(($diff / $prevVal) * 100, 1) : ($currVal > 0 ? 100.0 : 0.0);

            $comp[$key] = [
                'current' => $currVal,
                'previous' => $prevVal,
                'changePct' => $pctChange,
                'trend' => $pctChange >= 0 ? 'UP' : 'DOWN',
            ];
        }
        return $comp;
    }
}
