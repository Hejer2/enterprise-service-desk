<?php

namespace App\Tests\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Service\ExecutiveAnalyticsService;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase3eTest extends KernelTestCase
{
    private $em;
    private $analyticsService;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $ticketRepo = $this->em->getRepository(\App\Entity\Ticket::class);
        $userRepo = $this->em->getRepository(User::class);

        $this->analyticsService = new ExecutiveAnalyticsService(
            $this->em,
            $ticketRepo,
            $userRepo
        );
    }

    public function testAnalyticsDataCalculationAndStructure(): void
    {
        $data = $this->analyticsService->getAnalyticsData(['preset' => '30_days'], true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('totalTickets', $data['kpis']);
        $this->assertArrayHasKey('slaCompliancePct', $data['kpis']);
        $this->assertArrayHasKey('avgCsat', $data['kpis']);
        $this->assertArrayHasKey('comparison', $data);
    }
}
