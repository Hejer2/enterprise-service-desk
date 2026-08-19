<?php

namespace App\Tests\Service;

use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Repository\NotificationPreferenceRepository;
use App\Service\NotificationService;
use App\Service\RealtimeEventService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

class NotificationServiceTest extends TestCase
{
    private $em;
    private $mailer;
    private $logger;
    private $realtimeService;
    private $prefRepo;
    private $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->realtimeService = $this->createMock(RealtimeEventService::class);
        $this->prefRepo = $this->createMock(NotificationPreferenceRepository::class);

        $this->service = new NotificationService(
            $this->em,
            $this->mailer,
            $this->logger,
            $this->realtimeService,
            $this->prefRepo
        );
    }

    public function testNotifyCreatesNotificationAndPublishesRealtimeEvent(): void
    {
        $user = new User();
        $user->setEmail('user_notif@test.com');

        $pref = new NotificationPreference();
        $pref->setUser($user);
        $pref->setTicketAssignments(true);

        $this->prefRepo->method('getOrCreateForUser')->willReturn($pref);

        $this->realtimeService->expects($this->once())
            ->method('publishNotificationCreated');

        $this->service->notify($user, 'Test Title', 'Test Content', 'ticket_assigned', 123);
    }
}
