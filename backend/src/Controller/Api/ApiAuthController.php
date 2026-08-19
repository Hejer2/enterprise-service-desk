<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiAuthController extends AbstractController
{
    #[Route('/login', name: 'api_login_endpoint', methods: ['POST', 'OPTIONS'])]
    public function loginDummy(): JsonResponse
    {
        return $this->json(['status' => 'ok'], Response::HTTP_OK);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'phone' => $user->getPhone(),
            'role' => $user->getRoleEntity()->getName(),
            'roleDisplayName' => $user->getRoleEntity()->getDisplayName(),
            'permissions' => array_map(fn($p) => str_replace('ROLE_', '', $p->getName()), $user->getRoleEntity()->getPermissions()->toArray()),
            'language' => $user->getLanguage(),
            'theme' => $user->getTheme(),
            'profilePicture' => $user->getProfilePicture(),
        ], Response::HTTP_OK);
    }

    #[Route('/me/fcm', name: 'api_me_fcm', methods: ['POST'])]
    public function updateFcm(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        $user->setFcmToken($token);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'FCM Token updated successfully']);
    }

    #[Route('/me/preferences', name: 'api_me_preferences', methods: ['POST'])]
    public function updatePreferences(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $language = $data['language'] ?? null;
        $theme = $data['theme'] ?? null;

        if ($language && in_array($language, ['en', 'ar'])) {
            $user->setLanguage($language);
        }
        if ($theme && in_array($theme, ['light', 'dark'])) {
            $user->setTheme($theme);
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'language' => $user->getLanguage(),
            'theme' => $user->getTheme()
        ]);
    }

    #[Route('/me/change-password', name: 'api_me_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $currentPassword = $data['currentPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;
        $confirmPassword = $data['confirmPassword'] ?? null;

        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            return $this->json(['error' => 'Missing fields'], Response::HTTP_BAD_REQUEST);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->json(['error' => 'New passwords do not match'], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['error' => 'Current password is invalid'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($newPassword) < 8 ||
            !preg_match('/[A-Z]/', $newPassword) ||
            !preg_match('/[a-z]/', $newPassword) ||
            !preg_match('/[0-9]/', $newPassword) ||
            !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            return $this->json(['error' => 'Password must be at least 8 characters long, contain an uppercase letter, a lowercase letter, a number, and a special character'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Password updated successfully!']);
    }

    #[Route('/me/profile', name: 'api_me_update_profile', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $firstName = $data['firstName'] ?? null;
        $lastName = $data['lastName'] ?? null;
        $phone = $data['phone'] ?? null;
        $profilePicture = $data['profilePicture'] ?? null;

        // Check if file was uploaded via multipart/form-data
        $file = $request->files->get('profilePicture');
        if ($file) {
            if (\App\Service\FileSecurityValidator::validateUploadedFile($file)) {
                $newFilename = uniqid().'.'.$file->guessExtension();
                try {
                    $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/profile_pics';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $file->move($uploadDir, $newFilename);
                    $user->setProfilePicture('/uploads/profile_pics/'.$newFilename);
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Failed to upload profile picture'], Response::HTTP_BAD_REQUEST);
                }
            } else {
                return $this->json(['error' => 'Invalid file format for profile picture'], Response::HTTP_BAD_REQUEST);
            }
        } elseif ($profilePicture !== null) {
            if (str_starts_with($profilePicture, 'data:image/') || preg_match('/^[a-zA-Z0-9+\/]+={0,2}$/', substr($profilePicture, 0, 100)) && strlen($profilePicture) > 500) {
                // Handle base64 image
                try {
                    $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/profile_pics';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $ext = 'jpg';
                    $base64Content = $profilePicture;
                    if (str_contains($profilePicture, ';base64,')) {
                        $parts = explode(';base64,', $profilePicture);
                        if (str_contains($parts[0], 'png')) {
                            $ext = 'png';
                        } elseif (str_contains($parts[0], 'webp')) {
                            $ext = 'webp';
                        }
                        $base64Content = $parts[1];
                    }
                    $imageBinary = base64_decode($base64Content);
                    if ($imageBinary !== false) {
                        $newFilename = uniqid().'.'.$ext;
                        file_put_contents($uploadDir.'/'.$newFilename, $imageBinary);
                        $user->setProfilePicture('/uploads/profile_pics/'.$newFilename);
                    } else {
                        $user->setProfilePicture($profilePicture);
                    }
                } catch (\Exception $e) {
                    $user->setProfilePicture($profilePicture);
                }
            } else {
                $user->setProfilePicture($profilePicture);
            }
        }

        if ($firstName !== null && trim($firstName) !== '') {
            $user->setFirstName(trim($firstName));
        }
        if ($lastName !== null && trim($lastName) !== '') {
            $user->setLastName(trim($lastName));
        }
        if ($phone !== null) {
            $user->setPhone($phone);
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'phone' => $user->getPhone(),
            'profilePicture' => $user->getProfilePicture(),
        ]);
    }
}
