<?php

namespace App\Command;

use App\Entity\Ticket;
use App\Entity\TicketMessage;
use App\Service\TicketActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:migrate-ticket-activities',
    description: 'Migrate existing historical tickets and messages into ticket_activities'
)]
class MigrateTicketActivitiesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketActivityLogger $activityLogger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Migrating existing ticket historical data into ticket_activities...</info>');

        $tickets = $this->entityManager->getRepository(Ticket::class)->findAll();
        $migratedCount = 0;

        foreach ($tickets as $ticket) {
            // Check if ticket_created activity already exists for this ticket
            $existingCreated = $this->entityManager->getRepository('App\Entity\TicketActivity')->findOneBy([
                'ticket' => $ticket,
                'eventType' => 'ticket_created',
            ]);

            if (!$existingCreated) {
                $this->activityLogger->logActivity(
                    ticket: $ticket,
                    actor: $ticket->getCreatedBy(),
                    eventType: 'ticket_created',
                    previousValue: null,
                    newValue: $ticket->getStatus(),
                    description: sprintf('Ticket %s created by %s', $ticket->getTicketNumber(), $ticket->getCreatedBy()?->getFullName() ?? 'System'),
                    metadata: [
                        'source' => 'migration',
                        'category' => $ticket->getCategory(),
                        'priority' => $ticket->getPriority(),
                    ],
                    customCreatedAt: $ticket->getCreatedAt()
                );
                $migratedCount++;
            }

            // Migrate existing messages as comment_added activities
            $messages = $this->entityManager->getRepository(TicketMessage::class)->findBy(
                ['ticket' => $ticket],
                ['createdAt' => 'ASC']
            );

            foreach ($messages as $msg) {
                $existingMsgActivity = $this->entityManager->getRepository('App\Entity\TicketActivity')->findOneBy([
                    'ticket' => $ticket,
                    'eventType' => 'comment_added',
                    'createdAt' => $msg->getCreatedAt(),
                ]);

                if (!$existingMsgActivity) {
                    $this->activityLogger->logActivity(
                        ticket: $ticket,
                        actor: $msg->getSender(),
                        eventType: 'comment_added',
                        previousValue: null,
                        newValue: null,
                        description: sprintf('Comment added by %s', $msg->getSender()?->getFullName() ?? 'User'),
                        metadata: [
                            'source' => 'migration',
                            'messageId' => $msg->getId(),
                        ],
                        customCreatedAt: $msg->getCreatedAt()
                    );
                    $migratedCount++;
                }
            }
        }

        $output->writeln(sprintf('<comment>Migration complete! Processed %d historical records.</comment>', $migratedCount));

        return Command::SUCCESS;
    }
}
