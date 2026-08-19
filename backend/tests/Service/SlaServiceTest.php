<?php

namespace App\Tests\Service;

use App\Entity\SlaPolicy;
use App\Entity\Ticket;
use App\Entity\TicketSla;
use App\Service\SlaService;
use App\Service\TicketActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SlaServiceTest extends TestCase
{
    private $em;
    private $activityLogger;
    private $slaService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->activityLogger = $this->createMock(TicketActivityLogger::class);
        $this->slaService = new SlaService($this->em, $this->activityLogger);
    }

    public function testBusinessHoursCalculationDuringWeekday(): void
    {
        // Monday 10:00 + 120 minutes = Monday 12:00
        $start = new \DateTimeImmutable('2026-08-17 10:00:00'); // Monday
        $due = $this->slaService->calculateDueDate($start, 120);

        $this->assertEquals('2026-08-17 12:00:00', $due->format('Y-m-d H:i:s'));
    }

    public function testBusinessHoursCalculationOvernightSpan(): void
    {
        // Monday 17:00 + 120 minutes (1h remaining today -> 18:00, 1h tomorrow from 08:00) = Tuesday 09:00
        $start = new \DateTimeImmutable('2026-08-17 17:00:00'); // Monday
        $due = $this->slaService->calculateDueDate($start, 120);

        $this->assertEquals('2026-08-18 09:00:00', $due->format('Y-m-d H:i:s'));
    }

    public function testBusinessHoursCalculationWeekendSkip(): void
    {
        // Friday 17:00 + 120 minutes (1h Friday -> 18:00, Weekend closed, 1h Monday from 08:00) = Monday 09:00
        $start = new \DateTimeImmutable('2026-08-21 17:00:00'); // Friday
        $due = $this->slaService->calculateDueDate($start, 120);

        $this->assertEquals('2026-08-24 09:00:00', $due->format('Y-m-d H:i:s'));
    }

    public function testGetSlaStatusActiveAndAtRiskAndBreached(): void
    {
        $policy = new SlaPolicy();
        $policy->setResolutionMinutes(480); // 8 hours
        $policy->setWarningPercentage(80); // Warning after 6.4h (remaining <= 96 mins)

        $sla = new TicketSla();
        $sla->setSlaPolicy($policy);
        $sla->setResolutionDueAt(new \DateTimeImmutable('2026-08-17 18:00:00'));

        // 1. ACTIVE (remaining 200 mins > 96 mins)
        $statusActive = $this->slaService->getSlaStatus($sla, new \DateTimeImmutable('2026-08-17 14:40:00'));
        $this->assertEquals('ACTIVE', $statusActive);

        // 2. AT_RISK (remaining 60 mins <= 96 mins)
        $statusRisk = $this->slaService->getSlaStatus($sla, new \DateTimeImmutable('2026-08-17 17:00:00'));
        $this->assertEquals('AT_RISK', $statusRisk);

        // 3. BREACHED (after due date)
        $statusBreached = $this->slaService->getSlaStatus($sla, new \DateTimeImmutable('2026-08-17 18:05:00'));
        $this->assertEquals('BREACHED', $statusBreached);
    }
}
