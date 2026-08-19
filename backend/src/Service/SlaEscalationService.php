<?php

namespace App\Service;

use App\Entity\TicketSla;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SlaEscalationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SlaService $slaService,
        private NotificationService $notificationService,
        private TicketActivityLogger $ticketActivityLogger
    ) {}

    /**
     * Process warning, breach, and priority escalation for active SLA records.
     */
    public function processEscalations(): array
    {
        $slaRepo = $this->entityManager->getRepository(TicketSla::class);
        $activeSlas = $slaRepo->findActiveTicketSlas(100);

        $warningsCount = 0;
        $breachesCount = 0;
        $priorityEscalationsCount = 0;

        $now = new \DateTimeImmutable('now');

        foreach ($activeSlas as $sla) {
            $ticket = $sla->getTicket();
            if (!$ticket) continue;

            $status = $this->slaService->getSlaStatus($sla, $now);

            // 1. Check Warning Threshold
            if ($status === 'AT_RISK' && $sla->getWarningSentAt() === null) {
                $sla->setWarningSentAt($now);
                $sla->setResolutionStatus('AT_RISK');

                $this->ticketActivityLogger->logActivity(
                    ticket: $ticket,
                    actor: null,
                    eventType: 'sla_warning',
                    previousValue: 'ACTIVE',
                    newValue: 'AT_RISK',
                    description: sprintf('SLA Resolution approaching warning threshold (%s)', $sla->getResolutionDueAt()->format('Y-m-d H:i'))
                );

                if ($ticket->getAssignedTo()) {
                    $this->notificationService->sendSystemNotification(
                        user: $ticket->getAssignedTo(),
                        ticket: $ticket,
                        title: 'SLA Warning Alert',
                        message: sprintf('Ticket #%s is AT RISK of breaching SLA deadline (%s)', $ticket->getTicketNumber(), $sla->getResolutionDueAt()->format('d M H:i'))
                    );
                }

                $warningsCount++;
            }

            // 2. Check Breach Threshold
            if ($status === 'BREACHED' && $sla->getBreachedAt() === null) {
                $sla->setBreachedAt($now);
                $sla->setResolutionStatus('BREACHED');

                $this->ticketActivityLogger->logActivity(
                    ticket: $ticket,
                    actor: null,
                    eventType: 'sla_breached',
                    previousValue: 'AT_RISK',
                    newValue: 'BREACHED',
                    description: sprintf('SLA Resolution breached at %s', $now->format('Y-m-d H:i'))
                );

                // Notify Assigned Technician & Admins
                $recipients = [];
                if ($ticket->getAssignedTo()) {
                    $recipients[] = $ticket->getAssignedTo();
                }

                $admins = $this->entityManager->getRepository(User::class)->findByRoleName('ROLE_ADMIN');
                foreach ($admins as $admin) {
                    if (!in_array($admin, $recipients, true)) {
                        $recipients[] = $admin;
                    }
                }

                foreach ($recipients as $recipient) {
                    $this->notificationService->sendSystemNotification(
                        user: $recipient,
                        ticket: $ticket,
                        title: 'SLA Breach Alert',
                        message: sprintf('CRITICAL: Ticket #%s has BREACHED its Resolution SLA deadline!', $ticket->getTicketNumber())
                    );
                }

                $breachesCount++;

                // 3. Automatic Priority Escalation
                $currentPriority = $ticket->getPriority();
                $newPriority = $this->getEscalatedPriority($currentPriority);

                if ($newPriority !== $currentPriority) {
                    $ticket->setPriority($newPriority);

                    $this->ticketActivityLogger->logActivity(
                        ticket: $ticket,
                        actor: null,
                        eventType: 'sla_priority_escalated',
                        previousValue: $currentPriority,
                        newValue: $newPriority,
                        description: sprintf('Automatic priority escalation due to SLA breach (%s -> %s)', $currentPriority, $newPriority)
                    );

                    $priorityEscalationsCount++;
                }
            }
        }

        $this->entityManager->flush();

        return [
            'warnings' => $warningsCount,
            'breaches' => $breachesCount,
            'priorityEscalations' => $priorityEscalationsCount,
        ];
    }

    private function getEscalatedPriority(string $currentPriority): string
    {
        return match ($currentPriority) {
            'Low' => 'Medium',
            'Medium' => 'High',
            'High' => 'Critical',
            'Critical' => 'Critical',
            default => $currentPriority,
        };
    }
}
