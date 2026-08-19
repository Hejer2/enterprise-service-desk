<?php

namespace App\Command;

use App\Entity\KnowledgeArticle;
use App\Entity\KnowledgeCategory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:kb:seed-defaults',
    description: 'Seed initial Knowledge Base categories and published articles idempotently.'
)]
class SeedKnowledgeBaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $catRepo = $this->entityManager->getRepository(KnowledgeCategory::class);
        $articleRepo = $this->entityManager->getRepository(KnowledgeArticle::class);
        $userRepo = $this->entityManager->getRepository(User::class);

        $admin = $userRepo->findOneBy([]) ?: null;

        $categories = [
            ['name' => 'Account & Access', 'slug' => 'account-access', 'icon' => '🔑', 'description' => 'Password resets, 2FA, and SSO logins.', 'sort' => 1],
            ['name' => 'IT Support', 'slug' => 'it-support', 'icon' => '💻', 'description' => 'Workstation, software, printer, and Wi-Fi issues.', 'sort' => 2],
            ['name' => 'Machine Maintenance', 'slug' => 'machine-maintenance', 'icon' => '⚙️', 'description' => 'Equipment servicing, repairs, and diagnostics.', 'sort' => 3],
            ['name' => 'Leave Request & HR', 'slug' => 'leave-request-hr', 'icon' => '📋', 'description' => 'Vacation policies, sick leave, and HR procedures.', 'sort' => 4],
        ];

        $catMap = [];
        foreach ($categories as $c) {
            $cat = $catRepo->findOneBy(['slug' => $c['slug']]);
            if (!$cat) {
                $cat = new KnowledgeCategory();
                $cat->setName($c['name']);
                $cat->setSlug($c['slug']);
                $cat->setIcon($c['icon']);
                $cat->setDescription($c['description']);
                $cat->setSortOrder($c['sort']);
                $cat->setIsActive(true);
                $this->entityManager->persist($cat);
            }
            $catMap[$c['slug']] = $cat;
        }

        $this->entityManager->flush();

        $articles = [
            [
                'title' => 'How to reset your domain password',
                'slug' => 'how-to-reset-your-domain-password',
                'category' => 'account-access',
                'excerpt' => 'Step-by-step guide to resetting your company portal password.',
                'content' => "If you are locked out of your workstation or portal:\n\n1. Visit the self-service portal.\n2. Click 'Forgot Password'.\n3. Enter your corporate email address.\n4. Follow the SMS/Email verification instructions.\n\nIf you still experience issues, please create a support ticket.",
            ],
            [
                'title' => 'Connecting to Office Wi-Fi Network',
                'slug' => 'connecting-to-office-wifi-network',
                'category' => 'it-support',
                'excerpt' => 'Guidelines for connecting personal and corporate devices to Wi-Fi.',
                'content' => "To connect your device to the Secure Corporate Wi-Fi:\n\n- SSID: Corp-Enterprise-5G\n- Identity: Your domain username\n- Password: Your domain password\n- EAP Method: PEAP / MSCHAPv2",
            ],
            [
                'title' => 'Reporting Machine Equipment Malfunctions',
                'slug' => 'reporting-machine-equipment-malfunctions',
                'category' => 'machine-maintenance',
                'excerpt' => 'Safety protocol and reporting procedure for shop floor machinery.',
                'content' => "In case of machinery breakdown:\n\n1. Immediately press the Emergency Stop if safety is compromised.\n2. Turn off primary power toggle.\n3. Place 'OUT OF SERVICE' warning tag.\n4. Log a Machine Maintenance ticket immediately.",
            ],
        ];

        $createdCount = 0;
        foreach ($articles as $a) {
            $existing = $articleRepo->findOneBy(['slug' => $a['slug']]);
            if (!$existing && isset($catMap[$a['category']]) && $admin) {
                $art = new KnowledgeArticle();
                $art->setTitle($a['title']);
                $art->setSlug($a['slug']);
                $art->setCategory($catMap[$a['category']]);
                $art->setExcerpt($a['excerpt']);
                $art->setContent($a['content']);
                $art->setStatus('PUBLISHED');
                $art->setCreatedBy($admin);
                $art->setViewCount(12);
                $art->setHelpfulCount(5);

                $this->entityManager->persist($art);
                $createdCount++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Knowledge Base seeded successfully (%d new articles).', $createdCount));
        return Command::SUCCESS;
    }
}
