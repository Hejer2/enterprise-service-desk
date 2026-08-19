<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/notifications', name: 'app_notification_')]
class WebNotificationController extends AbstractController
{
    /**
     * Return the current user's latest 20 notifications as JSON.
     * Used by the sidebar notification panel via fetch().
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $notifications = $em->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            20
        );

        $unreadCount = $em->createQuery(
            'SELECT COUNT(n.id) FROM App\Entity\Notification n WHERE n.user = :user AND n.isRead = false'
        )->setParameter('user', $user)->getSingleScalarResult();

        $data = [];
        foreach ($notifications as $notif) {
            $data[] = [
                'id'        => $notif->getId(),
                'title'     => $notif->getTitle(),
                'content'   => $notif->getContent(),
                'type'      => $notif->getType(),
                'relatedId' => $notif->getRelatedId(),
                'isRead'    => $notif->isRead(),
                'createdAt' => $notif->getCreatedAt()->format('d M Y, H:i'),
            ];
        }

        return $this->json([
            'notifications' => $data,
            'unreadCount'   => (int) $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read and redirect to the related ticket.
     */
    #[Route('/{id}/read', name: 'read', methods: ['GET', 'POST'])]
    public function markRead(int $id, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $notification = $em->getRepository(Notification::class)->find($id);

        // Security: only the owner can mark their own notification
        if ($notification && $notification->getUser() === $user) {
            $notification->setIsRead(true);
            $em->flush();

            if ($notification->getRelatedId()) {
                return $this->redirectToRoute('app_ticket_show', ['id' => $notification->getRelatedId()]);
            }
        }

        return $this->redirectToRoute('app_dashboard');
    }

    /**
     * Mark all notifications as read for the current user.
     */
    #[Route('/read-all', name: 'read_all', methods: ['POST'])]
    public function markAllRead(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $em->createQuery(
            'UPDATE App\Entity\Notification n SET n.isRead = true WHERE n.user = :user AND n.isRead = false'
        )->setParameter('user', $user)->execute();

        return $this->json(['success' => true]);
    }
}
