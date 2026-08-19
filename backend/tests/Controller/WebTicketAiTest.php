<?php

namespace App\Tests\Controller;

use App\Controller\WebTicketController;
use App\Entity\Ticket;
use App\Entity\User;
use App\Service\AI\AiProviderService;
use App\Service\AI\AiService;
use App\Service\TicketManagerService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class WebTicketAiTest extends KernelTestCase
{
    private $em;
    private $container;
    private $aiService;
    private $ticketManager;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->container = $kernel->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();

        $provider = new AiProviderService();
        $ticketRepo = $this->em->getRepository(Ticket::class);
        $kbRepo = $this->em->getRepository(\App\Entity\KnowledgeArticle::class);

        $this->aiService = new AiService(
            $provider,
            $this->em,
            $ticketRepo,
            $kbRepo
        );

        $mailerMock = $this->createMock(\Symfony\Component\Mailer\MailerInterface::class);
        $loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $notificationService = new \App\Service\NotificationService($this->em, $mailerMock, $loggerMock);
        $auditLogger = new \App\Service\AuditLogger($this->em, new RequestStack());
        $ticketActivityLogger = new \App\Service\TicketActivityLogger($this->em);
        $this->ticketManager = new TicketManagerService($this->em, $notificationService, $auditLogger, $ticketActivityLogger);
    }

    public function testWebAiEndpoints(): void
    {
        $admin = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        $ticket = $this->em->getRepository(Ticket::class)->findOneBy([]);

        if (!$admin || !$ticket) {
            $this->markTestSkipped('Admin or ticket not found in database');
        }

        $tokenStorage = new \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage();
        $token = new UsernamePasswordToken($admin, 'main', $admin->getRoles());
        $tokenStorage->setToken($token);

        $mockContainer = $this->createMock(\Psr\Container\ContainerInterface::class);
        $mockContainer->method('has')->willReturnCallback(fn($id) => $id === 'security.token_storage');
        $mockContainer->method('get')->willReturnCallback(fn($id) => $id === 'security.token_storage' ? $tokenStorage : null);

        $controller = new WebTicketController();
        $controller->setContainer($mockContainer);

        // 1. Classify
        $req = new Request();
        $res = $controller->aiAction($ticket->getId(), 'classify', $req, $this->em, $this->ticketManager, $this->aiService);
        $this->assertEquals(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('category', $data);

        // 2. Reply
        $req = new Request([], [], [], [], [], [], json_encode(['action' => 'generate']));
        $res = $controller->aiAction($ticket->getId(), 'reply', $req, $this->em, $this->ticketManager, $this->aiService);
        $this->assertEquals(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('draft', $data);

        // 3. Summarize
        $req = new Request();
        $res = $controller->aiAction($ticket->getId(), 'summarize', $req, $this->em, $this->ticketManager, $this->aiService);
        $this->assertEquals(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('problem', $data);

        // 4. Similar
        $req = new Request();
        $res = $controller->aiAction($ticket->getId(), 'similar', $req, $this->em, $this->ticketManager, $this->aiService);
        $this->assertEquals(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('similarTickets', $data);

        // 5. Knowledge
        $req = new Request([], [], [], [], [], [], json_encode(['query' => 'password']));
        $res = $controller->aiAction($ticket->getId(), 'knowledge', $req, $this->em, $this->ticketManager, $this->aiService);
        $this->assertEquals(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('suggestedSolution', $data);

        // 6. Resolution
        $req = new Request();
        $res = $controller->aiAction($ticket->getId(), 'resolution', $req, $this->em, $this->ticketManager, $this->aiService);
        $this->assertEquals(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('recommendation', $data);
    }
}
