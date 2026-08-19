<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\UserNotification;
use App\Entity\User;
use App\Repository\NotificationPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private ?RealtimeEventService $realtimeEventService = null,
        private ?NotificationPreferenceRepository $preferenceRepository = null
    ) {}

    /**
     * Send notification to a specific user via all channels.
     */
    public function notify(User $user, string $title, string $content, string $type, ?int $relatedId = null): void
    {
        $prefs = $this->preferenceRepository ? $this->preferenceRepository->getOrCreateForUser($user) : null;

        // Check user preferences per type
        if ($prefs) {
            if ($type === 'ticket_assigned' && !$prefs->isTicketAssignments()) return;
            if ($type === 'ticket_reply' && !$prefs->isTicketReplies()) return;
            if ($type === 'ticket_status_changed' && !$prefs->isTicketStatusChanges()) return;
            if (($type === 'sla_warning' || $type === 'sla_breached') && !$prefs->isSlaAlerts()) return;
        }

        // 1. Create In-App Notification (Database Legacy & Phase 3C Persistent UserNotification)
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($title);
        $notification->setContent($content);
        $notification->setType($type);
        $notification->setRelatedId($relatedId);
        $this->entityManager->persist($notification);

        $userNotification = new UserNotification();
        $userNotification->setUser($user);
        $userNotification->setType($type);
        $userNotification->setTitle($title);
        $userNotification->setMessage($content);
        $userNotification->setEntityType('ticket');
        $userNotification->setEntityId($relatedId);
        $this->entityManager->persist($userNotification);

        $this->entityManager->flush();

        // 2. Publish Real-time Notification Event
        if ($this->realtimeEventService) {
            $this->realtimeEventService->publishNotificationCreated($userNotification);
        }

        // 3. Send Email Notification
        $this->sendEmail($user->getEmail(), $title, $content);
    }

    private function sendEmail(string $recipientEmail, string $subject, string $body): void
    {
        try {
            $email = (new Email())
                ->from('noreply@servicedesk.company.com')
                ->to($recipientEmail)
                ->subject("[Employee Service Desk] " . $subject)
                ->text($body)
                ->html("<div style='font-family: sans-serif; padding: 20px; color: #333;'>
                            <h2 style='color: #4f46e5;'>Employee Service Desk</h2>
                            <p><strong>$subject</strong></p>
                            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='line-height: 1.6;'>$body</p>
                            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                            <small style='color: #666;'>This is an automated system email. Please do not reply.</small>
                        </div>");

            $this->mailer->send($email);
            $this->logger->info("Email sent to $recipientEmail: $subject");
        } catch (\Exception $e) {
            $this->logger->error("Failed to send email to $recipientEmail: " . $e->getMessage());
        }
    }

    private function sendPushNotification(string $fcmToken, string $title, string $body, array $data = []): void
    {
        // FCM Push Notification Stub/Logger (ready for actual Firebase client payload)
        $this->logger->info("Sending FCM Push Notification to token: $fcmToken", [
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
        
        // This is where Firebase REST API v1 endpoint or Firebase SDK call would run:
        // $payload = [
        //     'message' => [
        //         'token' => $fcmToken,
        //         'notification' => ['title' => $title, 'body' => $body],
        //         'data' => $data
        //     ]
        // ];
    }
}
