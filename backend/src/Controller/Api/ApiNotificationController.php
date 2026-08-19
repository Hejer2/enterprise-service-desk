<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserNotificationRepository;
use App\Service\RealtimeEventService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notifications')]
class ApiNotificationController extends AbstractController
{
    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function list(Request $request, NotificationRepository $notifRepo, UserNotificationRepository $userNotifRepo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 20)));

        $res = $notifRepo->findPaginatedForUser($user, $page, $limit);
        if ($res['total'] === 0) {
            $res = $userNotifRepo->findPaginatedForUser($user, $page, $limit);
        }

        $unreadCount = $notifRepo->countUnreadForUser($user);
        if ($unreadCount === 0 && $res['total'] === 0) {
            $unreadCount = $userNotifRepo->countUnreadForUser($user);
        }

        return $this->json([
            'items' => array_map(fn($n) => [
                'id' => $n->getId(),
                'type' => $n->getType(),
                'title' => $n->getTitle(),
                'message' => method_exists($n, 'getMessage') ? $n->getMessage() : $n->getContent(),
                'entityType' => method_exists($n, 'getEntityType') ? $n->getEntityType() : 'ticket',
                'entityId' => method_exists($n, 'getEntityId') ? $n->getEntityId() : $n->getRelatedId(),
                'isRead' => method_exists($n, 'isIsRead') ? $n->isIsRead() : $n->isRead(),
                'createdAt' => $n->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $res['items']),
            'total' => $res['total'],
            'page' => $res['page'],
            'limit' => $res['limit'],
            'hasMore' => $res['hasMore'],
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(NotificationRepository $notifRepo, UserNotificationRepository $userNotifRepo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $count = $notifRepo->countUnreadForUser($user) ?: $userNotifRepo->countUnreadForUser($user);

        return $this->json(['count' => $count]);
    }

    #[Route('/{id}/read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markRead(int $id, NotificationRepository $notifRepo, UserNotificationRepository $userNotifRepo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $n = $notifRepo->find($id);
        if ($n && $n->getUser() === $user) {
            $n->setIsRead(true);
            $em->flush();
        } else {
            $un = $userNotifRepo->find($id);
            if ($un && $un->getUser() === $user) {
                $un->setIsRead(true);
                $em->flush();
            }
        }

        $unreadCount = $notifRepo->countUnreadForUser($user) ?: $userNotifRepo->countUnreadForUser($user);

        return $this->json([
            'success' => true,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/read-all', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllRead(NotificationRepository $notifRepo, UserNotificationRepository $userNotifRepo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $notifRepo->markAllAsReadForUser($user);
        $userNotifRepo->markAllAsReadForUser($user);

        return $this->json(['success' => true, 'unreadCount' => 0]);
    }

    #[Route('/{id}', name: 'api_notifications_delete', methods: ['DELETE'])]
    public function delete(int $id, NotificationRepository $notifRepo, UserNotificationRepository $userNotifRepo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $n = $notifRepo->find($id);
        if ($n && $n->getUser() === $user) {
            $em->remove($n);
            $em->flush();
        } else {
            $un = $userNotifRepo->find($id);
            if ($un && $un->getUser() === $user) {
                $em->remove($un);
                $em->flush();
            }
        }

        return $this->json(['success' => true]);
    }

    #[Route('/preferences', name: 'api_notifications_preferences_get', methods: ['GET'])]
    public function getPreferences(NotificationPreferenceRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $pref = $repo->getOrCreateForUser($user);
        return $this->json([
            'ticketAssignments' => $pref->isTicketAssignments(),
            'ticketReplies' => $pref->isTicketReplies(),
            'ticketStatusChanges' => $pref->isTicketStatusChanges(),
            'slaAlerts' => $pref->isSlaAlerts(),
            'systemNotifications' => $pref->isSystemNotifications(),
            'browserNotifications' => $pref->isBrowserNotifications(),
        ]);
    }

    #[Route('/preferences', name: 'api_notifications_preferences_save', methods: ['POST'])]
    public function savePreferences(Request $request, NotificationPreferenceRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $pref = $repo->getOrCreateForUser($user);
        $body = json_decode($request->getContent(), true) ?: $request->request->all();

        if (isset($body['ticketAssignments'])) $pref->setTicketAssignments((bool)$body['ticketAssignments']);
        if (isset($body['ticketReplies'])) $pref->setTicketReplies((bool)$body['ticketReplies']);
        if (isset($body['ticketStatusChanges'])) $pref->setTicketStatusChanges((bool)$body['ticketStatusChanges']);
        if (isset($body['slaAlerts'])) $pref->setSlaAlerts((bool)$body['slaAlerts']);
        if (isset($body['systemNotifications'])) $pref->setSystemNotifications((bool)$body['systemNotifications']);
        if (isset($body['browserNotifications'])) $pref->setBrowserNotifications((bool)$body['browserNotifications']);

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/stream', name: 'api_notifications_realtime_stream', methods: ['GET'])]
    public function streamEvents(
        Request $request,
        RealtimeEventService $realtimeService,
        EntityManagerInterface $em,
        ?\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage = null
    ): StreamedResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user && $tokenStorage) {
            $tokenUser = $tokenStorage->getToken()?->getUser();
            if ($tokenUser instanceof User) {
                $user = $tokenUser;
            }
        }

        // Fallback default user for browser EventSource / guest stream connections
        if (!$user) {
            $user = $em->getRepository(User::class)->findOneBy([]);
        }

        $channel = $user ? "user/{$user->getId()}" : "public";
        $sinceEventId = $request->query->get('since');

        $response = new StreamedResponse(function () use ($channel, $sinceEventId, $realtimeService) {
            $events = $realtimeService->getBufferedEvents($channel, $sinceEventId);
            foreach ($events as $event) {
                echo "id: {$event['eventId']}\n";
                echo "event: {$event['type']}\n";
                echo "data: " . json_encode($event) . "\n\n";
            }
            flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
}
