<?php

namespace App\Tests\Service;

use App\Entity\ApprovalRequest;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\ApprovalRequestRepository;
use App\Service\ApprovalService;
use App\Service\NotificationService;
use App\Service\TicketActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ApprovalServiceTest extends TestCase
{
    private $em;
    private $approvalRepo;
    private $notificationService;
    private $activityLogger;
    private $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->approvalRepo = $this->createMock(ApprovalRequestRepository::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->activityLogger = $this->createMock(TicketActivityLogger::class);

        $this->service = new ApprovalService(
            $this->em,
            $this->approvalRepo,
            $this->notificationService,
            $this->activityLogger
        );
    }

    public function testApprovalLifecycle(): void
    {
        $requester = new User();
        $approver = new User();
        $ticket = new Ticket();
        $ticket->setTicketNumber('TCK-APPR-1');
        $ticket->setStatus('Open');

        $request = $this->service->createApprovalRequest($ticket, $requester, $approver, 'Access request');

        $this->assertEquals('PENDING', $request->getStatus());
        $this->assertEquals('Waiting for Approval', $ticket->getStatus());

        $this->service->respondApproval($request, $approver, 'APPROVE', 'Approved by manager');

        $this->assertEquals('APPROVED', $request->getStatus());
        $this->assertEquals('In Progress', $ticket->getStatus());
    }
}
