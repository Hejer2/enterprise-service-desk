<?php

namespace App\Tests\Service;

use App\Entity\AutomationExecution;
use App\Entity\AutomationRule;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\AutomationExecutionRepository;
use App\Repository\AutomationRuleRepository;
use App\Repository\UserRepository;
use App\Service\AutomationEngineService;
use App\Service\NotificationService;
use App\Service\TicketActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AutomationEngineServiceTest extends TestCase
{
    private $em;
    private $ruleRepo;
    private $executionRepo;
    private $userRepo;
    private $activityLogger;
    private $notificationService;
    private $logger;
    private $engine;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->ruleRepo = $this->createMock(AutomationRuleRepository::class);
        $this->executionRepo = $this->createMock(AutomationExecutionRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->activityLogger = $this->createMock(TicketActivityLogger::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->engine = new AutomationEngineService(
            $this->em,
            $this->ruleRepo,
            $this->executionRepo,
            $this->userRepo,
            $this->activityLogger,
            $this->notificationService,
            $this->logger
        );
    }

    public function testProcessEventExecutesMatchingActions(): void
    {
        $rule = new AutomationRule();
        $rule->setName('Auto-Assign Critical');
        $rule->setTriggerType('TICKET_CREATED');
        $rule->setConditions(['priority' => 'Critical', 'assigned' => false]);
        $rule->setActions([['type' => 'CHANGE_PRIORITY', 'value' => 'High']]);

        $ticket = new Ticket();
        $ticket->setTicketNumber('TCK-TEST');
        $ticket->setPriority('Critical');

        $this->ruleRepo->method('findActiveByTrigger')->willReturn([$rule]);
        $this->executionRepo->method('findOneBy')->willReturn(null);

        $res = $this->engine->processEvent('TICKET_CREATED', $ticket);

        $this->assertEquals(1, $res['executed']);
        $this->assertEquals('High', $ticket->getPriority());
    }
}
