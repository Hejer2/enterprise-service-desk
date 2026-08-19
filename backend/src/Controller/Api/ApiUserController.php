<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Role;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users', name: 'api_users_')]
#[IsGranted('ROLE_ADMIN')]
class ApiUserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $users = $em->getRepository(User::class)->findAll();
        $data = [];

        foreach ($users as $u) {
            $data[] = [
                'id' => $u->getId(),
                'firstName' => $u->getFirstName(),
                'lastName' => $u->getLastName(),
                'fullName' => $u->getFullName(),
                'email' => $u->getEmail(),
                'phone' => $u->getPhone(),
                'role' => $u->getRoleEntity()?->getName() ?? 'User',
                'roleId' => $u->getRoleEntity()?->getId(),
                'createdAt' => $u->getCreatedAt()?->format('Y-m-d H:i:s'),
                'language' => 'EN',
                'theme' => 'System',
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/roles', name: 'roles', methods: ['GET'])]
    public function roles(EntityManagerInterface $em): JsonResponse
    {
        $roles = $em->getRepository(Role::class)->findAll();
        $data = [];
        foreach ($roles as $r) {
            $data[] = [
                'id' => $r->getId(),
                'name' => $r->getName(),
            ];
        }
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        AuditLogger $auditLogger,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $body = json_decode($request->getContent(), true);
        $email = $body['email'] ?? null;
        $firstName = $body['firstName'] ?? null;
        $lastName = $body['lastName'] ?? null;
        $phone = $body['phone'] ?? null;
        $password = $body['password'] ?? 'Password123!';
        $roleName = $body['role'] ?? 'ROLE_EMPLOYEE';

        if (!$email || !$firstName || !$lastName) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPhone($phone);

        $role = $em->getRepository(Role::class)->findOneBy(['name' => $roleName]);
        if ($role) {
            $user->setRoleEntity($role);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setPlainPassword($password);

        $em->persist($user);
        $em->flush();

        $auditLogger->log($currentUser, 'create_user_api', 'User', $user->getId());

        return $this->json([
            'id' => $user->getId(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'role' => $user->getRoleEntity()?->getName() ?? 'User',
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        AuditLogger $auditLogger,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);
        if (isset($body['firstName'])) $user->setFirstName($body['firstName']);
        if (isset($body['lastName'])) $user->setLastName($body['lastName']);
        if (isset($body['email'])) $user->setEmail($body['email']);
        if (isset($body['phone'])) $user->setPhone($body['phone']);

        if (isset($body['role'])) {
            $role = $em->getRepository(Role::class)->findOneBy(['name' => $body['role']]);
            if ($role) {
                $user->setRoleEntity($role);
            }
        }

        if (!empty($body['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $body['password']));
            $user->setPlainPassword($body['password']);
        }

        $em->flush();

        $auditLogger->log($currentUser, 'edit_user_api', 'User', $user->getId());

        return $this->json([
            'success' => true,
            'id' => $user->getId(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'role' => $user->getRoleEntity()?->getName() ?? 'User',
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE', 'POST'])]
    public function delete(
        int $id,
        EntityManagerInterface $em,
        AuditLogger $auditLogger
    ): JsonResponse {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($user === $currentUser) {
            return $this->json(['error' => 'Cannot delete your own account'], Response::HTTP_BAD_REQUEST);
        }

        $auditLogger->log($currentUser, 'delete_user_api', 'User', $user->getId());

        $em->remove($user);
        $em->flush();

        return $this->json(['success' => true], Response::HTTP_OK);
    }
}
