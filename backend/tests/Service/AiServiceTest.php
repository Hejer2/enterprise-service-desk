<?php

namespace App\Tests\Service;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\KnowledgeArticleRepository;
use App\Repository\TicketRepository;
use App\Service\AI\AiProviderInterface;
use App\Service\AI\AiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AiServiceTest extends TestCase
{
    private $provider;
    private $em;
    private $ticketRepo;
    private $kbRepo;
    private $service;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(AiProviderInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->ticketRepo = $this->createMock(TicketRepository::class);
        $this->kbRepo = $this->createMock(KnowledgeArticleRepository::class);

        $this->service = new AiService(
            $this->provider,
            $this->em,
            $this->ticketRepo,
            $this->kbRepo
        );
    }

    public function testClassifyTicketReturnsAdvisorySuggestions(): void
    {
        $ticket = new Ticket();
        $ticket->setTitle('Network Issue');
        $ticket->setDescription('Cannot connect to local server password=secret123');

        $this->provider->method('generateStructuredJson')->willReturn([
            'category' => 'IT Support',
            'priority' => 'High',
            'suggestedTeam' => 'Infrastructure Team',
            'confidence' => 0.95,
            'reason' => 'Network connectivity keywords',
        ]);

        $res = $this->service->classifyTicket($ticket);

        $this->assertEquals('IT Support', $res['category']);
        $this->assertEquals('High', $res['priority']);
        $this->assertEquals(0.95, $res['confidence']);
    }

    public function testGenerateReplyIsDraftOnlyAndNeverSendsAutomatically(): void
    {
        $ticket = new Ticket();
        $ticket->setTicketNumber('TCK-999');
        $ticket->setTitle('Printer Jam');

        $this->provider->method('completePrompt')->willReturn('Draft reply message.');

        $res = $this->service->generateReply($ticket);

        $this->assertTrue($res['isDraftOnly']);
        $this->assertEquals('Draft reply message.', $res['draft']);
    }
}
