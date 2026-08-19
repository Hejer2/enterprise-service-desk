<?php

namespace App\Tests\Service;

use App\Entity\Ticket;
use App\Entity\User;
use App\Service\RealtimeEventService;
use PHPUnit\Framework\TestCase;

class RealtimeEventServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        $this->service = new RealtimeEventService();
    }

    public function testPublishEventCreatesUniqueEventIdAndPayload(): void
    {
        $event = $this->service->publishEvent('user/10', 'ticket.created', ['ticketId' => 456]);

        $this->assertNotNull($event['eventId']);
        $this->assertEquals('user/10', $event['channel']);
        $this->assertEquals('ticket.created', $event['type']);
        $this->assertEquals(456, $event['payload']['ticketId']);
    }

    public function testGetBufferedEventsFiltersByChannel(): void
    {
        $this->service->publishEvent('user/99', 'test.event1', ['a' => 1]);
        $this->service->publishEvent('user/100', 'test.event2', ['b' => 2]);

        $events = $this->service->getBufferedEvents('user/99');
        $this->assertNotEmpty($events);
        $this->assertEquals('user/99', $events[0]['channel']);
    }
}
