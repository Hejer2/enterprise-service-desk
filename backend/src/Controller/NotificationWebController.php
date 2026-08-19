<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\NotificationPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationWebController extends AbstractController
{
    #[Route('/settings/notifications', name: 'app_settings_notifications', methods: ['GET', 'POST'])]
    public function settings(Request $request, NotificationPreferenceRepository $repo, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $pref = $repo->getOrCreateForUser($user);

        if ($request->isMethod('POST')) {
            $pref->setTicketAssignments($request->request->has('ticketAssignments'));
            $pref->setTicketReplies($request->request->has('ticketReplies'));
            $pref->setTicketStatusChanges($request->request->has('ticketStatusChanges'));
            $pref->setSlaAlerts($request->request->has('slaAlerts'));
            $pref->setSystemNotifications($request->request->has('systemNotifications'));
            $pref->setBrowserNotifications($request->request->has('browserNotifications'));

            $em->flush();
            $this->addFlash('success', 'Notification preferences saved successfully.');
        }

        return $this->render('settings/notifications.html.twig', [
            'preference' => $pref,
        ]);
    }
}
