<?php

namespace App\Tests\Controller;

use App\Entity\Ticket;
use App\Entity\TicketDependency;
use App\Entity\User;
use App\Entity\Role;
use App\Service\TicketManagerService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase3dTest extends KernelTestCase
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
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        
        $notifService = $this->createMock(\App\Service\NotificationService::class);
        $auditLogger = new \App\Service\AuditLogger($this->em);
        $activityLogger = new \App\Service\TicketActivityLogger($this->em);

        $this->ticketManager = new TicketManagerService(
            $this->em,
            $notifService,
            $auditLogger,
            $activityLogger
        );
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

    public function testTicketDependencyBlockingResolution(): void
    {
        $emp = $this->getOrCreateUser('emp_dep@test.com', 'ROLE_EMPLOYEE');

        // Ticket 1 (Blocker)
        $t1 = new Ticket();
        $t1->setTicketNumber('TCK-DEP-BLOCKER-' . uniqid());
        $t1->setTitle('Blocker Ticket');
        $t1->setDescription('Primary issue');
        $t1->setCategory('IT Support');
        $t1->setPriority('High');
        $t1->setStatus('Open');
        $t1->setCreatedBy($emp);
        $this->em->persist($t1);

        // Ticket 2 (Blocked)
        $t2 = new Ticket();
        $t2->setTicketNumber('TCK-DEP-BLOCKED-' . uniqid());
        $t2->setTitle('Blocked Ticket');
        $t2->setDescription('Secondary issue');
        $t2->setCategory('IT Support');
        $t2->setPriority('High');
        $t2->setStatus('Open');
        $t2->setCreatedBy($emp);
        $this->em->persist($t2);
        $this->em->flush();

        // Create Dependency: t2 is BLOCKED_BY t1
        $dep = new TicketDependency();
        $dep->setTicket($t2);
        $dep->setDependsOnTicket($t1);
        $dep->setDependencyType('BLOCKED_BY');
        $this->em->persist($dep);
        $this->em->flush();

        // Attempting to resolve t2 MUST throw LogicException
        $this->expectException(\LogicException::class);
        $this->ticketManager->updateStatus($t2, $emp, 'Resolved');
    }
}
