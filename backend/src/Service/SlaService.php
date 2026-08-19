<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\TicketSla;
use App\Entity\SlaPolicy;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SlaService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketActivityLogger $ticketActivityLogger
    ) {}

    /**
     * Attach and initialize SLA to a newly created ticket.
     */
    public function createTicketSla(Ticket $ticket): ?TicketSla
    {
        $priority = $ticket->getPriority() ?: 'Medium';
        $policyRepo = $this->entityManager->getRepository(SlaPolicy::class);
        $policy = $policyRepo->findOneBy(['priority' => $priority, 'isActive' => true]);

        if (!$policy) {
            return null;
        }

        $now = new \DateTimeImmutable('now');
        $firstResponseDue = $this->calculateDueDate($now, $policy->getFirstResponseMinutes());
        $resolutionDue = $this->calculateDueDate($now, $policy->getResolutionMinutes());

        $sla = new TicketSla();
        $sla->setTicket($ticket);
        $sla->setSlaPolicy($policy);
        $sla->setFirstResponseDueAt($firstResponseDue);
        $sla->setResolutionDueAt($resolutionDue);
        $sla->setFirstResponseStatus('ACTIVE');
        $sla->setResolutionStatus('ACTIVE');

        $this->entityManager->persist($sla);
        $this->entityManager->flush();

        return $sla;
    }

    /**
     * Calculate SLA due date incorporating centralized business hours (Mon-Fri 08:00-18:00).
     */
    public function calculateDueDate(\DateTimeInterface $start, int $minutes): \DateTimeImmutable
    {
        $current = \DateTime::createFromInterface($start);
        $remainingMinutes = $minutes;

        while ($remainingMinutes > 0) {
            // Roll forward if weekend
            $dayOfWeek = (int) $current->format('N'); // 1 = Mon, 7 = Sun
            if ($dayOfWeek >= 6) {
                // Saturday or Sunday ➔ Advance to Monday 08:00
                $daysToAdd = (8 - $dayOfWeek);
                $current->modify("+$daysToAdd days")->setTime(8, 0, 0);
                continue;
            }

            $hour = (int) $current->format('H');
            $minute = (int) $current->format('i');

            // Before work hours (before 08:00)
            if ($hour < 8) {
                $current->setTime(8, 0, 0);
                continue;
            }

            // After work hours (after or equal 18:00)
            if ($hour >= 18) {
                $current->modify('+1 day')->setTime(8, 0, 0);
                continue;
            }

            // Available minutes in current work day until 18:00
            $endOfDay = (clone $current)->setTime(18, 0, 0);
            $availableMinutes = (int) (($endOfDay->getTimestamp() - $current->getTimestamp()) / 60);

            if ($remainingMinutes <= $availableMinutes) {
                $current->modify("+$remainingMinutes minutes");
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $availableMinutes;
                $current->modify('+1 day')->setTime(8, 0, 0);
            }
        }

        return \DateTimeImmutable::createFromMutable($current);
    }

    /**
     * Compute current live status of a TicketSla: ACTIVE, AT_RISK, BREACHED, PAUSED, COMPLETED
     */
    public function getSlaStatus(TicketSla $sla, ?\DateTimeInterface $now = null): string
    {
        if ($sla->getResolutionStatus() === 'COMPLETED') {
            return 'COMPLETED';
        }

        if ($sla->getPausedAt() !== null) {
            return 'PAUSED';
        }

        $now = $now ? \DateTimeImmutable::createFromInterface($now) : new \DateTimeImmutable('now');

        if ($now > $sla->getResolutionDueAt()) {
            return 'BREACHED';
        }

        $policy = $sla->getSlaPolicy();
        if ($policy) {
            $totalMinutes = $policy->getResolutionMinutes();
            $warningMinutes = (int) ($totalMinutes * ($policy->getWarningPercentage() / 100));
            
            // Calculate remaining business minutes or timestamp diff
            $diffSeconds = $sla->getResolutionDueAt()->getTimestamp() - $now->getTimestamp();
            $remainingMinutes = (int) ($diffSeconds / 60);

            if ($remainingMinutes <= ($totalMinutes - $warningMinutes)) {
                return 'AT_RISK';
            }
        }

        return 'ACTIVE';
    }

    /**
     * Mark First Response SLA completed when a technician/staff member sends reply.
     */
    public function recordFirstResponse(Ticket $ticket, User $responder): void
    {
        $slaRepo = $this->entityManager->getRepository(TicketSla::class);
        $sla = $slaRepo->findOneBy(['ticket' => $ticket]);

        if (!$sla || $sla->getFirstResponseCompletedAt() !== null) {
            return;
        }

        $now = new \DateTimeImmutable('now');
        $sla->setFirstResponseCompletedAt($now);

        if ($now <= $sla->getFirstResponseDueAt()) {
            $sla->setFirstResponseStatus('COMPLETED');
        } else {
            $sla->setFirstResponseStatus('BREACHED');
        }

        $this->entityManager->flush();
    }

    /**
     * Mark Resolution SLA completed when ticket is marked Resolved or Closed.
     */
    public function recordResolution(Ticket $ticket): void
    {
        $slaRepo = $this->entityManager->getRepository(TicketSla::class);
        $sla = $slaRepo->findOneBy(['ticket' => $ticket]);

        if (!$sla || $sla->getResolutionCompletedAt() !== null) {
            return;
        }

        $now = new \DateTimeImmutable('now');
        $sla->setResolutionCompletedAt($now);

        if ($now <= $sla->getResolutionDueAt()) {
            $sla->setResolutionStatus('COMPLETED');
        } else {
            $sla->setResolutionStatus('BREACHED');
        }

        $this->entityManager->flush();
    }

    /**
     * Pause SLA tracking when ticket status becomes waiting or paused.
     */
    public function pauseSla(TicketSla $sla): void
    {
        if ($sla->getPausedAt() !== null) {
            return;
        }

        $sla->setPausedAt(new \DateTimeImmutable('now'));
        $sla->setResolutionStatus('PAUSED');
        $this->entityManager->flush();
    }

    /**
     * Resume SLA tracking and extend due dates by accumulated paused duration.
     */
    public function resumeSla(TicketSla $sla): void
    {
        if ($sla->getPausedAt() === null) {
            return;
        }

        $now = new \DateTimeImmutable('now');
        $pausedSeconds = $now->getTimestamp() - $sla->getPausedAt()->getTimestamp();
        $pausedMinutes = (int) ceil($pausedSeconds / 60);

        $sla->setTotalPausedMinutes($sla->getTotalPausedMinutes() + $pausedMinutes);
        $sla->setPausedAt(null);

        // Extend due dates
        $sla->setFirstResponseDueAt($sla->getFirstResponseDueAt()->modify("+$pausedMinutes minutes"));
        $sla->setResolutionDueAt($sla->getResolutionDueAt()->modify("+$pausedMinutes minutes"));

        $sla->setResolutionStatus($this->getSlaStatus($sla, $now));
        $this->entityManager->flush();
    }

    /**
     * Restart SLA when employee reopens a resolved ticket.
     */
    public function restartSlaForReopenedTicket(Ticket $ticket, User $actor): void
    {
        $slaRepo = $this->entityManager->getRepository(TicketSla::class);
        $sla = $slaRepo->findOneBy(['ticket' => $ticket]);

        if (!$sla) {
            $this->createTicketSla($ticket);
            return;
        }

        $now = new \DateTimeImmutable('now');
        $policy = $sla->getSlaPolicy();
        if ($policy) {
            $newResolutionDue = $this->calculateDueDate($now, $policy->getResolutionMinutes());
            $sla->setResolutionDueAt($newResolutionDue);
        }

        $sla->setResolutionCompletedAt(null);
        $sla->setResolutionStatus('ACTIVE');
        $sla->setWarningSentAt(null);
        $sla->setBreachedAt(null);

        $this->entityManager->flush();

        $this->ticketActivityLogger->logActivity(
            ticket: $ticket,
            actor: $actor,
            eventType: 'sla_restarted',
            previousValue: 'Resolved',
            newValue: 'Reopened',
            description: 'SLA deadline restarted upon ticket reopen'
        );
    }
}
