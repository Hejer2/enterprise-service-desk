<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\TicketActivity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TicketActivityLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Log a ticket activity event in the database.
     * Enforces value change validation (ignores no-op changes).
     */
    public function logActivity(
        Ticket $ticket,
        ?User $actor,
        string $eventType,
        ?string $previousValue = null,
        ?string $newValue = null,
        ?string $description = null,
        ?array $metadata = null,
        ?\DateTimeImmutable $customCreatedAt = null
    ): ?TicketActivity {
        // Requirement: Do not record if previousValue and newValue are identical
        if ($previousValue !== null && $newValue !== null && $previousValue === $newValue) {
            return null;
        }

        $activity = new TicketActivity();
        $activity->setTicket($ticket);
        $activity->setActor($actor);
        $activity->setEventType($eventType);
        $activity->setPreviousValue($previousValue);
        $activity->setNewValue($newValue);
        $activity->setDescription($description);
        $activity->setMetadata($metadata);

        if ($customCreatedAt !== null) {
            $activity->setCreatedAt($customCreatedAt);
        }

        $this->entityManager->persist($activity);
        // Flushed along with parent transaction or flushed immediately
        $this->entityManager->flush();

        return $activity;
    }
}
