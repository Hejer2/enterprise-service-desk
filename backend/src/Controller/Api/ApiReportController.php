<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ApiReportController extends AbstractController
{
    #[Route('/reports', name: 'reports', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ReportService $reportService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $roleName = $user->getRoleEntity()?->getName();
        if ($roleName === 'ROLE_EMPLOYEE') {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $preset = $request->query->get('date_preset', 'last_30_days');
        $customStart = $request->query->get('start_date');
        $customEnd = $request->query->get('end_date');
        $category = $request->query->get('category');
        $techId = $request->query->get('technician') ? (int) $request->query->get('technician') : null;

        [$startDate, $endDate] = $reportService->resolveDatePreset($preset, $customStart, $customEnd);
        $reportData = $reportService->getReportData($user, $startDate, $endDate, $category, $techId);

        // Serialize ticket list for JSON response
        $serializedTickets = [];
        foreach ($reportData['tickets'] as $ticket) {
            $serializedTickets[] = [
                'id' => $ticket->getId(),
                'ticketNumber' => $ticket->getTicketNumber(),
                'title' => $ticket->getTitle(),
                'category' => $ticket->getCategory(),
                'priority' => $ticket->getPriority(),
                'status' => $ticket->getStatus(),
                'createdAt' => $ticket->getCreatedAt()->format('Y-m-d\TH:i:sP'),
                'closedAt' => $ticket->getClosedAt() ? $ticket->getClosedAt()->format('Y-m-d\TH:i:sP') : null,
                'createdBy' => $ticket->getCreatedBy() ? $ticket->getCreatedBy()->getFirstName() . ' ' . $ticket->getCreatedBy()->getLastName() : 'Unknown',
                'assignedTo' => $ticket->getAssignedTo() ? $ticket->getAssignedTo()->getFirstName() . ' ' . $ticket->getAssignedTo()->getLastName() : null,
            ];
        }

        return $this->json([
            'totalTickets' => $reportData['totalTickets'],
            'openCount' => $reportData['openCount'],
            'closedCount' => $reportData['closedCount'],
            'avgResolutionTime' => $reportData['avgResolutionTime'],
            'csatAverage' => $reportData['csatAverage'],
            'csatCount' => $reportData['csatCount'],
            'csatDistribution' => $reportData['csatDistribution'],
            'byStatus' => $reportData['byStatus'],
            'byPriority' => $reportData['byPriority'],
            'byCategory' => $reportData['byCategory'],
            'byTechnician' => $reportData['byTechnician'],
            'byDepartment' => $reportData['byDepartment'],
            'monthlyTrend' => $reportData['monthlyTrend'],
            'tickets' => $serializedTickets,
        ]);
    }
}
