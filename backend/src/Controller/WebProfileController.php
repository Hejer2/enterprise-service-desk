<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(
        \Symfony\Component\HttpFoundation\Request $request, 
        \Doctrine\ORM\EntityManagerInterface $em,
        \App\Service\AuditLogger $auditLogger
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $phone = $request->request->get('phone');

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setPhone($phone);

            // Profile Picture Upload
            $file = $request->files->get('profilePicture');
            if ($file) {
                if (\App\Service\FileSecurityValidator::validateUploadedFile($file)) {
                    $newFilename = uniqid().'.'.$file->guessExtension();
                    try {
                        $file->move(
                            $this->getParameter('kernel.project_dir').'/public/uploads/profile_pics',
                            $newFilename
                        );
                        $user->setProfilePicture('/uploads/profile_pics/'.$newFilename);
                    } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                        $this->addFlash('error', 'Failed to upload profile picture.');
                    }
                } else {
                    $this->addFlash('error', 'Invalid file format or dangerous extension for profile picture.');
                }
            }

            $em->flush();
            $auditLogger->log($user, 'update_profile', 'User', $user->getId());
            $this->addFlash('success', 'Profile updated successfully!');
            
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}
