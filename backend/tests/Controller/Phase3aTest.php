<?php

namespace App\Tests\Controller;

use App\Entity\SlaPolicy;
use App\Entity\Ticket;
use App\Entity\TicketSla;
use App\Entity\User;
use App\Entity\Role;
use App\Service\SlaService;
use App\Service\SlaEscalationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase3aTest extends KernelTestCase
{
    private $em;
    private $slaService;
    private $escalationService;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $activityLogger = new \App\Service\TicketActivityLogger($this->em);
        $this->slaService = new SlaService($this->em, $activityLogger);

        $this->getOrCreateSlaPolicy('Critical');
        $this->getOrCreateSlaPolicy('High');
        $this->getOrCreateSlaPolicy('Medium');
        $this->getOrCreateSlaPolicy('Low');
    }

    private function getOrCreateSlaPolicy(string $priority): \App\Entity\SlaPolicy
    {
        $policy = $this->em->getRepository(\App\Entity\SlaPolicy::class)->findOneBy(['priority' => $priority]);
        if (!$policy) {
            $policy = new \App\Entity\SlaPolicy();
            $policy->setName($priority . ' SLA');
            $policy->setPriority($priority);
            $policy->setFirstResponseMinutes(60);
            $policy->setResolutionMinutes(240);
            $policy->setIsActive(true);
            $this->em->persist($policy);
            $this->em->flush();
        }
        return $policy;
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

    public function testTicketSlaCreationAndLifecycle(): void
    {
        $emp = $this->getOrCreateUser('emp_sla@test.com', 'ROLE_EMPLOYEE');

        $ticket = new Ticket();
        $ticket->setTicketNumber('TCK-SLA-' . uniqid());
        $ticket->setTitle('SLA Lifecycle Test Ticket');
        $ticket->setDescription('Testing SLA creation and status calculation');
        $ticket->setCategory('IT Support');
        $ticket->setPriority('Critical');
        $ticket->setStatus('Open');
        $ticket->setCreatedBy($emp);

        $this->em->persist($ticket);
        $this->em->flush();

        $sla = $this->slaService->createTicketSla($ticket);

        $this->assertNotNull($sla);
        $this->assertEquals('Critical', $sla->getSlaPolicy()->getPriority());
        $this->assertEquals('ACTIVE', $sla->getFirstResponseStatus());
        $this->assertEquals('ACTIVE', $sla->getResolutionStatus());
        $this->assertNotNull($sla->getFirstResponseDueAt());
        $this->assertNotNull($sla->getResolutionDueAt());
    }

    public function testSlaPauseAndResume(): void
    {
        $emp = $this->getOrCreateUser('emp_sla_pause@test.com', 'ROLE_EMPLOYEE');

        $ticket = new Ticket();
        $ticket->setTicketNumber('TCK-SLA-PAUSE-' . uniqid());
        $ticket->setTitle('SLA Pause Test Ticket');
        $ticket->setDescription('Testing SLA pause and resume logic');
        $ticket->setCategory('IT Support');
        $ticket->setPriority('High');
        $ticket->setStatus('Waiting for Employee');
        $ticket->setCreatedBy($emp);

        $this->em->persist($ticket);
        $this->em->flush();

        $sla = $this->slaService->createTicketSla($ticket);
        $this->assertNotNull($sla);

        $originalDue = $sla->getResolutionDueAt();

        // Pause SLA
        $this->slaService->pauseSla($sla);
        $this->assertNotNull($sla->getPausedAt());
        $this->assertEquals('PAUSED', $sla->getResolutionStatus());

        // Resume SLA
        $this->slaService->resumeSla($sla);
        $this->assertNull($sla->getPausedAt());
        $this->assertNotEquals('PAUSED', $sla->getResolutionStatus());
    }
}
