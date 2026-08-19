<?php

namespace App\Command;

use App\Service\SlaEscalationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tickets:process-sla',
    description: 'Process SLA warning thresholds, breaches, and automatic priority escalations.'
)]
class ProcessSlaCommand extends Command
{
    public function __construct(
        private SlaEscalationService $escalationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $results = $this->escalationService->processEscalations();

        $io->success(sprintf(
            'Processed SLA: %d warning(s) logged, %d breach(es) detected, %d priority escalation(s) applied.',
            $results['warnings'],
            $results['breaches'],
            $results['priorityEscalations']
        ));

        return Command::SUCCESS;
    }
}
