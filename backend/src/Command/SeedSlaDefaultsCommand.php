<?php

namespace App\Command;

use App\Entity\SlaPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sla:seed-defaults',
    description: 'Seed default SLA policies (Critical, High, Medium, Low) idempotently.'
)]
class SeedSlaDefaultsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repo = $this->entityManager->getRepository(SlaPolicy::class);

        $defaults = [
            [
                'name' => 'Critical Priority SLA',
                'priority' => 'Critical',
                'firstResponse' => 15,
                'resolution' => 240, // 4 hours
                'warning' => 80,
            ],
            [
                'name' => 'High Priority SLA',
                'priority' => 'High',
                'firstResponse' => 30,
                'resolution' => 480, // 8 hours
                'warning' => 80,
            ],
            [
                'name' => 'Medium Priority SLA',
                'priority' => 'Medium',
                'firstResponse' => 120, // 2 hours
                'resolution' => 1440, // 24 hours
                'warning' => 80,
            ],
            [
                'name' => 'Low Priority SLA',
                'priority' => 'Low',
                'firstResponse' => 480, // 8 hours
                'resolution' => 4320, // 72 hours
                'warning' => 80,
            ],
        ];

        $created = 0;
        foreach ($defaults as $def) {
            $existing = $repo->findOneBy(['priority' => $def['priority'], 'isActive' => true]);
            if (!$existing) {
                $policy = new SlaPolicy();
                $policy->setName($def['name']);
                $policy->setPriority($def['priority']);
                $policy->setFirstResponseMinutes($def['firstResponse']);
                $policy->setResolutionMinutes($def['resolution']);
                $policy->setWarningPercentage($def['warning']);
                $policy->setIsActive(true);

                $this->entityManager->persist($policy);
                $created++;
            }
        }

        if ($created > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Seeded %d default SLA policy/policies.', $created));
        } else {
            $io->info('Default SLA policies already exist. No action taken.');
        }

        return Command::SUCCESS;
    }
}
