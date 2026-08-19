<?php

namespace App\Tests\Service;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\ExecutiveAnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class ExecutiveAnalyticsServiceTest extends TestCase
{
    private $em;
    private $ticketRepo;
    private $userRepo;
    private $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->ticketRepo = $this->createMock(TicketRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);

        $this->service = new ExecutiveAnalyticsService(
            $this->em,
            $this->ticketRepo,
            $this->userRepo
        );
    }

    public function testGetAnalyticsDataStructure(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn([]);
        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getSingleResult')->willReturn(['avgRating' => 4.5, 'totalRatings' => 10]);

        $qb->method('getQuery')->willReturn($query);
        $this->em->method('createQueryBuilder')->willReturn($qb);
        $this->userRepo->method('findByRoleName')->willReturn([]);

        $data = $this->service->getAnalyticsData(['preset' => '30_days'], true);

        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('period', $data);
        $this->assertArrayHasKey('comparison', $data);
        $this->assertArrayHasKey('technicians', $data);
    }
}
