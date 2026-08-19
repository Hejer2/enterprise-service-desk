<?php

namespace App\Tests\Service;

use App\Entity\SlaPolicy;
use App\Entity\Ticket;
use App\Entity\TicketSla;
use App\Entity\User;
use App\Repository\TicketSlaRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\SlaEscalationService;
use App\Service\SlaService;
use App\Service\TicketActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SlaEscalationServiceTest extends TestCase
{
    private $em;
    private $slaService;
    private $notificationService;
    private $activityLogger;
    private $escalationService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->slaService = $this->createMock(SlaService::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->activityLogger = $this->createMock(TicketActivityLogger::class);

        $this->escalationService = new SlaEscalationService(
            $this->em,
            $this->slaService,
            $this->notificationService,
            $this->activityLogger
        );
    }

    public function testProcessEscalationWarningAndBreachOnce(): void
    {
        $policy = new SlaPolicy();
        $policy->setResolutionMinutes(240);

        $ticket = new Ticket();
        $ticket->setTicketNumber('TCK-TEST');
        $ticket->setPriority('Medium');

        $sla = new TicketSla();
        $sla->setTicket($ticket);
        $sla->setSlaPolicy($policy);
        $sla->setResolutionDueAt(new \DateTimeImmutable('-10 minutes'));

        $slaRepo = $this->createMock(TicketSlaRepository::class);
        $slaRepo->method('findActiveTicketSlas')->willReturn([$sla]);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByRoleName')->willReturn([]);

        $this->em->method('getRepository')->willReturnCallback(function ($class) use ($slaRepo, $userRepo) {
            if ($class === TicketSla::class) return $slaRepo;
            if ($class === User::class) return $userRepo;
            return null;
        });

        $this->slaService->method('getSlaStatus')->willReturn('BREACHED');

        $result = $this->escalationService->processEscalations();

        $this->assertEquals(1, $result['breaches']);
        $this->assertEquals(1, $result['priorityEscalations']);
        $this->assertEquals('High', $ticket->getPriority());
        $this->assertNotNull($sla->getBreachedAt());

        // Second run must NOT duplicate escalation or priority change
        $result2 = $this->escalationService->processEscalations();
        $this->assertEquals(0, $result2['breaches']);
        $this->assertEquals(0, $result2['priorityEscalations']);
    }
}
