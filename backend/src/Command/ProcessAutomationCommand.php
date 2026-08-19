<?php

namespace App\Command;

use App\Service\AutomationReminderService;
use App\Service\RecurringTicketService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:automation:process',
    description: 'Process scheduled automation rules, recurring maintenance tickets, and reminder alerts.'
)]
class ProcessAutomationCommand extends Command
{
    public function __construct(
        private RecurringTicketService $recurringService,
        private AutomationReminderService $reminderService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Process recurring maintenance tickets
        $recRes = $this->recurringService->processDueRecurringTickets();

        // 2. Process automated reminders
        $remRes = $this->reminderService->processReminders();

        $io->success(sprintf(
            'Automation Processing Completed: %d recurring ticket(s) generated, %d reminder(s) sent.',
            $recRes['created'],
            $remRes['remindersSent']
        ));

        return Command::SUCCESS;
    }
}
