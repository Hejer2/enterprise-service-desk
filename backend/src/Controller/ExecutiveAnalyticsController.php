<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ExecutiveAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/executive-analytics')]
class ExecutiveAnalyticsController extends AbstractController
{
    private function checkAccess(): User
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Login required.');
        }

        $role = $user->getRoleEntity()?->getName();
        if ($role !== 'ROLE_ADMIN' && $role !== 'ROLE_HR') {
            throw new AccessDeniedException('Access denied. Executive Analytics requires Administrator or HR permissions.');
        }

        return $user;
    }

    #[Route('', name: 'app_executive_analytics_index', methods: ['GET'])]
    public function index(Request $request, ExecutiveAnalyticsService $analyticsService): Response
    {
        $this->checkAccess();

        $filters = [
            'preset' => $request->query->get('preset', '30_days'),
            'from' => $request->query->get('from'),
            'to' => $request->query->get('to'),
            'category' => $request->query->get('category'),
            'priority' => $request->query->get('priority'),
            'status' => $request->query->get('status'),
        ];

        $data = $analyticsService->getAnalyticsData($filters, true);

        return $this->render('executive_analytics/index.html.twig', [
            'data' => $data,
            'filters' => $filters,
        ]);
    }

    #[Route('/export/csv', name: 'app_executive_analytics_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, ExecutiveAnalyticsService $analyticsService): Response
    {
        $this->checkAccess();

        $filters = [
            'preset' => $request->query->get('preset', '30_days'),
            'from' => $request->query->get('from'),
            'to' => $request->query->get('to'),
        ];

        $data = $analyticsService->getAnalyticsData($filters, true);
        $kpis = $data['kpis'];
        $comp = $data['comparison'];

        $csv = "Executive Analytics Summary Report\n";
        $csv .= "Period," . $data['period']['from'] . " to " . $data['period']['to'] . "\n\n";
        $csv .= "Metric,Current Value,Previous Value,Change %\n";
        $csv .= "Total Tickets," . $kpis['totalTickets'] . "," . ($comp['totalTickets']['previous'] ?? 0) . "," . ($comp['totalTickets']['changePct'] ?? 0) . "%\n";
        $csv .= "Open Tickets," . $kpis['openTickets'] . "," . ($comp['openTickets']['previous'] ?? 0) . "," . ($comp['openTickets']['changePct'] ?? 0) . "%\n";
        $csv .= "Resolved Tickets," . $kpis['resolvedTickets'] . "," . ($comp['resolvedTickets']['previous'] ?? 0) . "," . ($comp['resolvedTickets']['changePct'] ?? 0) . "%\n";
        $csv .= "SLA Compliance %," . $kpis['slaCompliancePct'] . "%," . ($comp['slaCompliancePct']['previous'] ?? 0) . "%," . ($comp['slaCompliancePct']['changePct'] ?? 0) . "%\n";
        $csv .= "Average CSAT," . $kpis['avgCsat'] . "," . ($comp['avgCsat']['previous'] ?? 0) . "," . ($comp['avgCsat']['changePct'] ?? 0) . "%\n";
        $csv .= "Automation Executions," . $kpis['automationExecutions'] . "," . ($comp['automationExecutions']['previous'] ?? 0) . "," . ($comp['automationExecutions']['changePct'] ?? 0) . "%\n";

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="executive_analytics_' . date('Y-m-d') . '.csv"',
        ]);
    }

    #[Route('/export/pdf', name: 'app_executive_analytics_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, ExecutiveAnalyticsService $analyticsService): Response
    {
        $this->checkAccess();

        $filters = [
            'preset' => $request->query->get('preset', '30_days'),
            'from' => $request->query->get('from'),
            'to' => $request->query->get('to'),
        ];

        $data = $analyticsService->getAnalyticsData($filters, true);
        $kpis = $data['kpis'];

        $html = "<html><head><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ccc;padding:8px;text-align:left;}</style></head><body>";
        $html .= "<h1>Executive Analytics Summary Report</h1>";
        $html .= "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";
        $html .= "<table><tr><th>Metric</th><th>Current Value</th></tr>";
        $html .= "<tr><td>Total Tickets</td><td>" . $kpis['totalTickets'] . "</td></tr>";
        $html .= "<tr><td>Open Tickets</td><td>" . $kpis['openTickets'] . "</td></tr>";
        $html .= "<tr><td>Resolved Tickets</td><td>" . $kpis['resolvedTickets'] . "</td></tr>";
        $html .= "<tr><td>SLA Compliance %</td><td>" . $kpis['slaCompliancePct'] . "%</td></tr>";
        $html .= "<tr><td>Average CSAT</td><td>" . $kpis['avgCsat'] . " / 5.0</td></tr>";
        $html .= "<tr><td>Automation Executions</td><td>" . $kpis['automationExecutions'] . "</td></tr>";
        $html .= "</table></body></html>";

        return new Response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="executive_analytics_' . date('Y-m-d') . '.html"',
        ]);
    }

    #[Route('/ai/insights', name: 'app_executive_analytics_ai_insights', methods: ['POST', 'GET'])]
    public function aiInsights(Request $request, ExecutiveAnalyticsService $analyticsService): Response
    {
        $this->checkAccess();

        $filters = [
            'preset' => $request->query->get('preset', '30_days'),
            'from' => $request->query->get('from'),
            'to' => $request->query->get('to'),
            'category' => $request->query->get('category'),
            'priority' => $request->query->get('priority'),
            'status' => $request->query->get('status'),
        ];

        $insights = $analyticsService->generateStrategicAiInsights($filters);

        return $this->json(['insights' => $insights]);
    }
}
