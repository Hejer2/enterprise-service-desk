<?php

namespace App\Command;

use App\Entity\AutomationRule;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:automation:seed-defaults',
    description: 'Seed default enterprise automation and workflow rules idempotently.'
)]
class SeedAutomationDefaultsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ruleRepo = $this->entityManager->getRepository(AutomationRule::class);
        $userRepo = $this->entityManager->getRepository(User::class);
        $admin = $userRepo->findOneBy([]) ?: null;

        $rules = [
            [
                'name' => 'Auto-Assign Critical IT Tickets',
                'description' => 'Automatically route and assign Critical unassigned tickets to the IT technician with lowest workload.',
                'trigger' => 'TICKET_CREATED',
                'priority' => 10,
                'conditions' => ['priority' => 'Critical', 'assigned' => false],
                'actions' => [
                    ['type' => 'ASSIGN_TECHNICIAN', 'value' => 'AUTO_LOAD_BALANCED'],
                    ['type' => 'SEND_NOTIFICATION', 'value' => 'Critical ticket created and auto-assigned.'],
                ],
            ],
            [
                'name' => 'Route Machine Maintenance Tickets',
                'description' => 'Route shop floor machinery maintenance tickets to Maintenance Technicians.',
                'trigger' => 'TICKET_CREATED',
                'priority' => 8,
                'conditions' => ['category' => 'Machine Maintenance', 'assigned' => false],
                'actions' => [
                    ['type' => 'ASSIGN_TECHNICIAN', 'value' => 'AUTO_LOAD_BALANCED'],
                ],
            ],
            [
                'name' => 'Escalate Reopened Ticket Priority',
                'description' => 'Increase priority to High when a ticket is reopened by an employee.',
                'trigger' => 'TICKET_REOPENED',
                'priority' => 5,
                'conditions' => ['status' => 'Reopened'],
                'actions' => [
                    ['type' => 'CHANGE_PRIORITY', 'value' => 'High'],
                ],
            ],
        ];

        $createdCount = 0;
        foreach ($rules as $r) {
            $existing = $ruleRepo->findOneBy(['name' => $r['name']]);
            if (!$existing) {
                $rule = new AutomationRule();
                $rule->setName($r['name']);
                $rule->setDescription($r['description']);
                $rule->setTriggerType($r['trigger']);
                $rule->setPriority($r['priority']);
                $rule->setConditions($r['conditions']);
                $rule->setActions($r['actions']);
                $rule->setIsActive(true);
                $rule->setCreatedBy($admin);

                $this->entityManager->persist($rule);
                $createdCount++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Seeded %d default automation rule(s).', $createdCount));
        return Command::SUCCESS;
    }
}
