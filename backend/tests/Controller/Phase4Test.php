<?php

namespace App\Tests\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Role;
use App\Service\AI\AiProviderService;
use App\Service\AI\AiService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase4Test extends KernelTestCase
{
    private $em;
    private $aiService;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $provider = new AiProviderService();
        $ticketRepo = $this->em->getRepository(Ticket::class);
        $kbRepo = $this->em->getRepository(\App\Entity\KnowledgeArticle::class);

        $this->aiService = new AiService(
            $provider,
            $this->em,
            $ticketRepo,
            $kbRepo
        );
    }

    public function testAiClassificationFallbackAndStructure(): void
    {
        $ticket = new Ticket();
        $ticket->setTitle('VPN Connection Dropping');
        $ticket->setDescription('VPN disconnects every 5 minutes.');
        $ticket->setCategory('IT Support');
        $ticket->setPriority('Medium');

        $result = $this->aiService->classifyTicket($ticket);

        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('priority', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('reason', $result);
    }
}
