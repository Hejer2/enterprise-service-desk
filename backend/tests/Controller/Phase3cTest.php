<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\UserNotification;
use App\Repository\UserNotificationRepository;
use App\Repository\NotificationPreferenceRepository;
use App\Service\NotificationService;
use App\Service\RealtimeEventService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase3cTest extends KernelTestCase
{
    private $em;
    private $notificationService;
    private $realtimeService;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $this->realtimeService = new RealtimeEventService();
        $prefRepo = $this->em->getRepository(\App\Entity\NotificationPreference::class);

        $mailer = $this->createMock(\Symfony\Component\Mailer\MailerInterface::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->notificationService = new NotificationService(
            $this->em,
            $mailer,
            $logger,
            $this->realtimeService,
            $prefRepo
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

    public function testNotificationCreationAndUnreadCounting(): void
    {
        $user = $this->getOrCreateUser('user_p3c@test.com', 'ROLE_EMPLOYEE');

        $this->notificationService->notify($user, 'Ticket Assigned', 'Ticket #100 assigned to you', 'ticket_assigned', 100);

        $notifRepo = $this->em->getRepository(UserNotification::class);
        $unread = $notifRepo->countUnreadForUser($user);

        $this->assertGreaterThanOrEqual(1, $unread);

        // Mark as read
        $notifRepo->markAllAsReadForUser($user);
        $unreadAfter = $notifRepo->countUnreadForUser($user);
        $this->assertEquals(0, $unreadAfter);
    }
}
