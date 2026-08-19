<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_ADMIN')]
class WebUserController extends AbstractController
{
    #[Route('/users', name: 'app_user_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/create', name: 'app_user_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em, 
        AuditLogger $auditLogger,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $roles = $em->getRepository(Role::class)->findAll();

        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName'));
            $user->setLastName($request->request->get('lastName'));
            $user->setPhone($request->request->get('phone'));

            $roleId = $request->request->get('role');
            if ($roleId) {
                $role = $em->getRepository(Role::class)->find($roleId);
                if ($role) {
                    $user->setRoleEntity($role);
                }
            }

            // Set Password
            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setPlainPassword($password);
            }

            $em->persist($user);
            $em->flush();

            $auditLogger->log($this->getUser(), 'create_user', 'User', $user->getId(), [
                'createdUser' => $user->getEmail(),
            ]);

            $this->addFlash('success', "User {$user->getFullName()} created successfully.");
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/create.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id, 
        Request $request, 
        EntityManagerInterface $em, 
        AuditLogger $auditLogger,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $roles = $em->getRepository(Role::class)->findAll();

        if ($request->isMethod('POST')) {
            $user->setFirstName($request->request->get('firstName'));
            $user->setLastName($request->request->get('lastName'));
            $user->setPhone($request->request->get('phone'));
            $user->setEmail($request->request->get('email'));

            $roleId = $request->request->get('role');
            if ($roleId) {
                $role = $em->getRepository(Role::class)->find($roleId);
                if ($role) {
                    $user->setRoleEntity($role);
                }
            }

            // Update Password if provided
            $password = $request->request->get('password');
            if (!empty($password)) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setPlainPassword($password);
            }

            $em->flush();

            $auditLogger->log($this->getUser(), 'edit_user', 'User', $user->getId(), [
                'editedUser' => $user->getEmail(),
            ]);

            $this->addFlash('success', "User {$user->getFullName()} updated successfully.");
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em, AuditLogger $auditLogger): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        // Prevent admin from deleting themselves
        if ($user === $this->getUser()) {
            $this->addFlash('error', "You cannot delete your own account.");
            return $this->redirectToRoute('app_user_index');
        }

        $userEmail = $user->getEmail();
        $auditLogger->log($this->getUser(), 'delete_user', 'User', $user->getId(), [
            'deletedUser' => $userEmail,
        ]);

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', "User {$userEmail} deleted successfully.");
        
        return $this->redirectToRoute('app_user_index');
    }
}
