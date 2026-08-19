<?php

namespace App\Command;

use App\Entity\CannedResponse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed-canned-responses',
    description: 'Seeds initial canned response templates safely without creating duplicates.'
)]
class SeedCannedResponsesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->em->getRepository(User::class)->findOneBy([]);
        if (!$admin) {
            $output->writeln('<error>No user found in database to attach canned responses to.</error>');
            return Command::FAILURE;
        }

        $seeds = [
            [
                'title' => 'Password Reset',
                'category' => 'IT Support',
                'content' => 'Please restart your workstation and try logging in again. If the issue persists, we can reset your credentials.'
            ],
            [
                'title' => 'Printer Issue',
                'category' => 'Hardware',
                'content' => 'Please verify that the printer is powered on and connected to the network.'
            ],
            [
                'title' => 'Ticket Received',
                'category' => 'General',
                'content' => 'We have received your request and are currently investigating the issue.'
            ],
        ];

        $createdCount = 0;
        foreach ($seeds as $seed) {
            $existing = $this->em->getRepository(CannedResponse::class)->findOneBy(['title' => $seed['title']]);
            if (!$existing) {
                $canned = new CannedResponse();
                $canned->setTitle($seed['title']);
                $canned->setCategory($seed['category']);
                $canned->setContent($seed['content']);
                $canned->setCreatedBy($admin);
                $canned->setIsActive(true);

                $this->em->persist($canned);
                $createdCount++;
            }
        }

        $this->em->flush();
        $output->writeln(sprintf('<info>Canned response seeding complete. %d new template(s) created.</info>', $createdCount));

        return Command::SUCCESS;
    }
}
