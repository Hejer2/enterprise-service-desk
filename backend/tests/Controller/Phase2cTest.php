<?php

namespace App\Tests\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Role;
use App\Service\TicketManagerService;
use App\Service\ReportService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase2cTest extends KernelTestCase
{
    private $em;
    private $ticketManager;
    private $reportService;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        $this->em = $container->get('doctrine')->getManager();

        $mailerMock = $this->createMock(\Symfony\Component\Mailer\MailerInterface::class);
        $loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);

        $notificationService = new \App\Service\NotificationService($this->em, $mailerMock, $loggerMock);
        $auditLogger = new \App\Service\AuditLogger($this->em, new \Symfony\Component\HttpFoundation\RequestStack());
        $ticketActivityLogger = new \App\Service\TicketActivityLogger($this->em);

        $this->ticketManager = new TicketManagerService($this->em, $notificationService, $auditLogger, $ticketActivityLogger);
        $this->reportService = new ReportService($this->em);
    }

    private function getOrCreateUser(string $email, string $roleName): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $role = $this->em->getRepository(Role::class)->findOneBy(['name' => $roleName]);
            if (!$role) {
                $role = new Role();
                $role->setName($roleName);
                $role->setDisplayName($roleName);
                $this->em->persist($role);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setFirstName(ucfirst(explode('@', $email)[0]));
            $user->setLastName('User');
            $user->setPassword('password123');
            $user->setRoleEntity($role);

            $this->em->persist($user);
            $this->em->flush();
        }
        return $user;
    }

    private function createSampleTicket(User $creator, string $status = 'Open', string $category = 'IT Support'): Ticket
    {
        $ticket = new Ticket();
        $ticket->setTicketNumber('TCK-' . uniqid());
        $ticket->setTitle('Phase 2C Bulk & Report Test');
        $ticket->setDescription('Test description for Phase 2C');
        $ticket->setCategory($category);
        $ticket->setPriority('Medium');
        $ticket->setStatus($status);
        $ticket->setCreatedBy($creator);

        $this->em->persist($ticket);
        $this->em->flush();

        return $ticket;
    }

    public function testBulkStatusChangeSuccess(): void
    {
        $tech = $this->getOrCreateUser('tech_bulk_status@test.com', 'ROLE_IT_TECH');
        $ticket1 = $this->createSampleTicket($tech, 'Open');
        $ticket2 = $this->createSampleTicket($tech, 'Open');

        $result = $this->ticketManager->bulkUpdateTickets($tech, [$ticket1->getId(), $ticket2->getId()], 'status', 'In Progress');

        $this->assertCount(2, $result['updated']);
        $this->assertEmpty($result['failed']);
        $this->assertEquals('In Progress', $ticket1->getStatus());
        $this->assertEquals('In Progress', $ticket2->getStatus());
    }

    public function testBulkAssignmentSuccess(): void
    {
        $admin = $this->getOrCreateUser('admin_bulk_assign@test.com', 'ROLE_ADMIN');
        $tech = $this->getOrCreateUser('target_tech@test.com', 'ROLE_IT_TECH');
        $ticket = $this->createSampleTicket($admin, 'Open');

        $result = $this->ticketManager->bulkUpdateTickets($admin, [$ticket->getId()], 'assign', $tech->getId());

        $this->assertCount(1, $result['updated']);
        $this->assertEquals($tech->getId(), $ticket->getAssignedTo()->getId());
    }

    public function testPartialFailureBehaviorOnUnauthorizedTicket(): void
    {
        $employee = $this->getOrCreateUser('emp_bulk@test.com', 'ROLE_EMPLOYEE');
        $otherEmployee = $this->getOrCreateUser('other_emp_bulk@test.com', 'ROLE_EMPLOYEE');
        
        $myTicket = $this->createSampleTicket($employee, 'Resolved');
        $unauthorizedTicket = $this->createSampleTicket($otherEmployee, 'Resolved');

        $result = $this->ticketManager->bulkUpdateTickets($employee, [$myTicket->getId(), $unauthorizedTicket->getId()], 'close', 'Closed');

        $this->assertContains($myTicket->getId(), $result['updated']);
        $this->assertCount(1, $result['failed']);
        $this->assertEquals($unauthorizedTicket->getId(), $result['failed'][0]['id']);
    }

    public function testReportServiceDataCalculationAndDatePresets(): void
    {
        $admin = $this->getOrCreateUser('admin_report@test.com', 'ROLE_ADMIN');
        $this->createSampleTicket($admin, 'Open');
        $this->createSampleTicket($admin, 'Closed');

        [$startDate, $endDate] = $this->reportService->resolveDatePreset('last_30_days');
        $reportData = $this->reportService->getReportData($admin, $startDate, $endDate);

        $this->assertArrayHasKey('totalTickets', $reportData);
        $this->assertArrayHasKey('byStatus', $reportData);
        $this->assertArrayHasKey('csatAverage', $reportData);
        $this->assertGreaterThanOrEqual(2, $reportData['totalTickets']);
    }
}
