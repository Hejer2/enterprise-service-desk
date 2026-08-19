<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\UserNotification;

class RealtimeEventService
{
    private static array $eventBuffer = [];

    public function publishEvent(string $channel, string $eventType, array $payload): array
    {
        $eventId = sprintf('evt_%s_%s', uniqid(), bin2hex(random_bytes(4)));
        $event = [
            'eventId' => $eventId,
            'channel' => $channel,
            'type' => $eventType,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.v\Z'),
            'payload' => $payload,
        ];

        // Store event in buffer for active stream listeners
        self::$eventBuffer[] = $event;
        if (count(self::$eventBuffer) > 200) {
            array_shift(self::$eventBuffer);
        }

        return $event;
    }

    public function publishTicketCreated(Ticket $ticket): void
    {
        $this->publishEvent("ticket/{$ticket->getId()}", 'ticket.created', $this->serializeTicketPayload($ticket));
        $this->publishEvent('channel/tickets', 'ticket.created', $this->serializeTicketPayload($ticket));
    }

    public function publishTicketUpdated(Ticket $ticket, string $updateType = 'updated'): void
    {
        $this->publishEvent("ticket/{$ticket->getId()}", "ticket.{$updateType}", $this->serializeTicketPayload($ticket));
        $this->publishEvent('channel/tickets', "ticket.{$updateType}", $this->serializeTicketPayload($ticket));
    }

    public function publishTicketAssigned(Ticket $ticket, ?User $assignee): void
    {
        $payload = $this->serializeTicketPayload($ticket);
        $payload['assignedTo'] = $assignee ? $assignee->getFullName() : null;

        $this->publishEvent("ticket/{$ticket->getId()}", 'ticket.assigned', $payload);
        if ($assignee) {
            $this->publishEvent("user/{$assignee->getId()}", 'ticket.assigned', $payload);
        }
    }

    public function publishTicketMessageCreated(Ticket $ticket, array $messageData): void
    {
        $payload = [
            'ticketId' => $ticket->getId(),
            'message' => $messageData,
        ];
        $this->publishEvent("ticket/{$ticket->getId()}", 'ticket.message_created', $payload);
    }

    public function publishNotificationCreated(UserNotification $notification): void
    {
        $payload = [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'entityType' => $notification->getEntityType(),
            'entityId' => $notification->getEntityId(),
            'isRead' => $notification->isIsRead(),
            'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
        $this->publishEvent("user/{$notification->getUser()->getId()}", 'notification.created', $payload);
    }

    public function getBufferedEvents(string $channel, ?string $sinceEventId = null): array
    {
        $results = [];
        $foundSince = $sinceEventId === null;

        foreach (self::$eventBuffer as $evt) {
            if (!$foundSince) {
                if ($evt['eventId'] === $sinceEventId) {
                    $foundSince = true;
                }
                continue;
            }

            if ($evt['channel'] === $channel || str_starts_with($evt['channel'], 'channel/')) {
                $results[] = $evt;
            }
        }

        return $results;
    }

    private function serializeTicketPayload(Ticket $t): array
    {
        return [
            'id' => $t->getId(),
            'ticketNumber' => $t->getTicketNumber(),
            'title' => $t->getTitle(),
            'category' => $t->getCategory(),
            'priority' => $t->getPriority(),
            'status' => $t->getStatus(),
            'createdBy' => $t->getCreatedBy()->getFullName(),
            'assignedTo' => $t->getAssignedTo() ? $t->getAssignedTo()->getFullName() : null,
            'updatedAt' => $t->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
