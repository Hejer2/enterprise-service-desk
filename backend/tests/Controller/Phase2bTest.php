<?php

namespace App\Tests\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\CannedResponse;
use App\Entity\CsatRating;
use App\Service\TicketManagerService;
use App\Service\FileSecurityValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase2bTest extends KernelTestCase
{
    private $em;
    private $ticketManager;

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

    private function createSampleTicket(User $creator, string $status = 'Resolved'): Ticket
    {
        $ticket = new Ticket();
        $ticket->setTicketNumber('TCK-' . uniqid());
        $ticket->setTitle('Phase 2B Test Ticket');
        $ticket->setDescription('Test description for Phase 2B');
        $ticket->setCategory('IT Support');
        $ticket->setPriority('Medium');
        $ticket->setStatus($status);
        $ticket->setCreatedBy($creator);

        $this->em->persist($ticket);
        $this->em->flush();

        return $ticket;
    }

    public function testAuthorizedReopenSuccess(): void
    {
        $employee = $this->getOrCreateUser('emp_reopen@test.com', 'ROLE_EMPLOYEE');
        $ticket = $this->createSampleTicket($employee, 'Resolved');

        $this->ticketManager->reopenTicket($ticket, $employee, 'Issue returned after update.');

        $this->assertEquals('Reopened', $ticket->getStatus());
    }

    public function testUnauthorizedReopenRejection(): void
    {
        $employee = $this->getOrCreateUser('emp_owner@test.com', 'ROLE_EMPLOYEE');
        $otherEmployee = $this->getOrCreateUser('other_emp@test.com', 'ROLE_EMPLOYEE');
        $ticket = $this->createSampleTicket($employee, 'Resolved');

        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->ticketManager->reopenTicket($ticket, $otherEmployee, 'Unauthorized reopen attempt');
    }

    public function testReopenReasonRequired(): void
    {
        $employee = $this->getOrCreateUser('emp_reason@test.com', 'ROLE_EMPLOYEE');
        $ticket = $this->createSampleTicket($employee, 'Resolved');

        $this->expectException(\InvalidArgumentException::class);
        $this->ticketManager->reopenTicket($ticket, $employee, '   ');
    }

    public function testCsatSubmission1StarAnd5StarWithComment(): void
    {
        $employee = $this->getOrCreateUser('emp_csat@test.com', 'ROLE_EMPLOYEE');
        $ticket1 = $this->createSampleTicket($employee, 'Resolved');
        $ticket2 = $this->createSampleTicket($employee, 'Closed');

        $csat1 = $this->ticketManager->submitCsatRating($ticket1, $employee, 1, 'Unsatisfied with resolution speed.');
        $this->assertEquals(1, $csat1->getRating());
        $this->assertEquals('Unsatisfied with resolution speed.', $csat1->getComment());

        $csat2 = $this->ticketManager->submitCsatRating($ticket2, $employee, 5, null);
        $this->assertEquals(5, $csat2->getRating());
        $this->assertNull($csat2->getComment());
    }

    public function testDuplicateCsatRejection(): void
    {
        $employee = $this->getOrCreateUser('emp_dup_csat@test.com', 'ROLE_EMPLOYEE');
        $ticket = $this->createSampleTicket($employee, 'Closed');

        $this->ticketManager->submitCsatRating($ticket, $employee, 5, 'Great support!');

        $this->expectException(\LogicException::class);
        $this->ticketManager->submitCsatRating($ticket, $employee, 4, 'Duplicate rating');
    }

    public function testUnauthorizedCsatRejection(): void
    {
        $owner = $this->getOrCreateUser('csat_owner@test.com', 'ROLE_EMPLOYEE');
        $attacker = $this->getOrCreateUser('csat_attacker@test.com', 'ROLE_EMPLOYEE');
        $ticket = $this->createSampleTicket($owner, 'Closed');

        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->ticketManager->submitCsatRating($ticket, $attacker, 1, 'Fake rating');
    }

    public function testCannedResponseCreationAndFetching(): void
    {
        $tech = $this->getOrCreateUser('tech_canned@test.com', 'ROLE_IT_TECH');

        $canned = new CannedResponse();
        $canned->setTitle('Password Reset Test');
        $canned->setCategory('IT Support');
        $canned->setContent('Please restart work station.');
        $canned->setCreatedBy($tech);

        $this->em->persist($canned);
        $this->em->flush();

        $fetched = $this->em->getRepository(CannedResponse::class)->findActiveResponses();
        $this->assertNotEmpty($fetched);
    }

    public function testFileSecurityValidatorAuthoritativeCheck(): void
    {
        $validImage = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==";
        $decodedImage = base64_decode($validImage);

        $isValid = FileSecurityValidator::validateRawFileContent($decodedImage, 'test.png');
        $this->assertTrue($isValid);

        $forbiddenExe = "MZ9000000000000000";
        $isExeValid = FileSecurityValidator::validateRawFileContent($forbiddenExe, 'malware.exe');
        $this->assertFalse($isExeValid);
    }
}
