<?php

namespace App\Controller;

use App\Entity\Setting;
use App\Entity\User;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\NotificationPreferenceRepository;

class WebSettingController extends AbstractController
{
    #[Route('/settings', name: 'app_setting_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher,
        AuditLogger $auditLogger,
        NotificationPreferenceRepository $notifPrefRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $activeTab = $request->query->get('tab', 'preferences');
        
        if ($activeTab === 'security' && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access Denied: Only administrators can access the Password & Security tab.');
            return $this->redirectToRoute('app_setting_index', ['tab' => 'preferences']);
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'update_profile') {
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
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Failed to upload profile picture.');
                        }
                    } else {
                        $this->addFlash('error', 'Invalid file format or dangerous extension for profile picture.');
                    }
                }

                $em->flush();
                $auditLogger->log($user, 'update_profile', 'User', $user->getId());
                $this->addFlash('success', 'Profile details updated successfully!');
                return $this->redirectToRoute('app_setting_index', ['tab' => 'profile']);

            } elseif ($action === 'update_user_settings') {
                $language = $request->request->get('language', 'en');
                $theme = $request->request->get('theme', 'light');

                if (in_array($language, ['en', 'ar'])) {
                    $user->setLanguage($language);
                    try {
                        $request->getSession()->set('_locale', $language);
                    } catch (\Exception $e) {}
                }
                if (in_array($theme, ['light', 'dark'])) {
                    $user->setTheme($theme);
                }

                $em->flush();
                $auditLogger->log($user, 'update_user_settings', 'User', $user->getId());
                $this->addFlash('success', 'Preferences updated successfully.');
                return $this->redirectToRoute('app_setting_index', ['tab' => 'preferences']);

            } elseif ($action === 'update_notification_preferences') {
                $pref = $notifPrefRepo->getOrCreateForUser($user);
                $pref->setTicketAssignments($request->request->has('ticketAssignments'));
                $pref->setTicketReplies($request->request->has('ticketReplies'));
                $pref->setTicketStatusChanges($request->request->has('ticketStatusChanges'));
                $pref->setSlaAlerts($request->request->has('slaAlerts'));
                $pref->setSystemNotifications($request->request->has('systemNotifications'));
                $pref->setBrowserNotifications($request->request->has('browserNotifications'));

                $em->flush();
                $auditLogger->log($user, 'update_notification_preferences', 'NotificationPreference', $pref->getId());
                $this->addFlash('success', 'Notification preferences saved successfully.');
                return $this->redirectToRoute('app_setting_index', ['tab' => 'notifications']);

            } elseif ($action === 'change_password') {
                if (!$this->isGranted('ROLE_ADMIN')) {
                    $this->addFlash('error', 'Only administrators can change security settings.');
                    return $this->redirectToRoute('app_setting_index', ['tab' => 'preferences']);
                }

                $currentPassword = $request->request->get('currentPassword');
                $newPassword = $request->request->get('newPassword');
                $confirmPassword = $request->request->get('confirmPassword');

                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New password and confirmation password do not match.');
                    return $this->redirectToRoute('app_setting_index', ['tab' => 'security']);
                }

                if (strlen($newPassword) < 8 || 
                    !preg_match('/[A-Z]/', $newPassword) || 
                    !preg_match('/[a-z]/', $newPassword) || 
                    !preg_match('/[0-9]/', $newPassword) || 
                    !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                    $this->addFlash('error', 'Security Requirements: Password must be at least 8 characters long, contain an uppercase letter, a lowercase letter, a number, and a special character.');
                    return $this->redirectToRoute('app_setting_index', ['tab' => 'security']);
                }

                if ($passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    $em->flush();

                    $auditLogger->log($user, 'change_password', 'User', $user->getId());
                    $this->addFlash('success', 'Password updated successfully!');
                } else {
                    $this->addFlash('error', 'Current password is invalid.');
                }
                return $this->redirectToRoute('app_setting_index', ['tab' => 'security']);

            } elseif ($action === 'update_system_settings') {
                if (!$this->isGranted('ROLE_ADMIN')) {
                    throw $this->createAccessDeniedException('Only administrators can edit system configurations.');
                }

                $settingsData = $request->request->all();
                unset($settingsData['action']);

                foreach ($settingsData as $key => $value) {
                    $setting = $em->getRepository(Setting::class)->findOneBy(['settingKey' => $key]);
                    if (!$setting) {
                        $setting = new Setting();
                        $setting->setSettingKey($key);
                        $setting->setCategory('system');
                    }
                    $setting->setSettingValue($value);
                    $em->persist($setting);
                }

                $em->flush();
                $auditLogger->log($user, 'update_settings', 'Setting');
                $this->addFlash('success', 'Application settings updated successfully.');
                return $this->redirectToRoute('app_setting_index', ['tab' => 'system']);
            }
        }

        // Seed default settings if empty
        $defaultSettings = [
            'company_name' => 'Enterprise Corporation',
            'support_email' => 'support@company.com',
            'sla_low_hours' => '48',
            'sla_medium_hours' => '24',
            'sla_high_hours' => '8',
            'sla_critical_hours' => '2',
        ];

        foreach ($defaultSettings as $key => $val) {
            $setting = $em->getRepository(Setting::class)->findOneBy(['settingKey' => $key]);
            if (!$setting) {
                $setting = new Setting();
                $setting->setSettingKey($key);
                $setting->setSettingValue($val);
                $setting->setCategory('system');
                $em->persist($setting);
            }
        }
        $em->flush();

        $settings = $em->getRepository(Setting::class)->findAll();
        $notifPreference = $notifPrefRepo->getOrCreateForUser($user);

        return $this->render('setting/index.html.twig', [
            'settings' => $settings,
            'user' => $user,
            'active_tab' => $activeTab,
            'notification_preference' => $notifPreference,
        ]);
    }
}
