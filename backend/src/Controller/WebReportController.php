<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class WebReportController extends AbstractController
{
    #[Route('/reports', name: 'app_report_index')]
    public function index(Request $request, EntityManagerInterface $em, ReportService $reportService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $roleName = $user->getRoleEntity()?->getName();
        if ($roleName === 'ROLE_EMPLOYEE') {
            throw $this->createAccessDeniedException('Access denied. Employees are not authorized to view reports.');
        }

        $preset = $request->query->get('date_preset', 'last_30_days');
        $customStart = $request->query->get('start_date');
        $customEnd = $request->query->get('end_date');
        $category = $request->query->get('category');
        $techId = $request->query->get('technician') ? (int) $request->query->get('technician') : null;

        [$startDate, $endDate] = $reportService->resolveDatePreset($preset, $customStart, $customEnd);
        $reportData = $reportService->getReportData($user, $startDate, $endDate, $category, $techId);

        $techs = $em->createQuery(
            'SELECT u FROM App\Entity\User u JOIN u.roleEntity r WHERE r.name IN (:roles)'
        )
        ->setParameter('roles', ['ROLE_IT_TECH', 'ROLE_MAINTENANCE_TECH', 'ROLE_HR'])
        ->getResult();

        return $this->render('report/index.html.twig', array_merge($reportData, [
            'technicians' => $techs,
            'selected_preset' => $preset,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'selected_category' => $category,
            'selected_technician' => $techId,
        ]));
    }

    #[Route('/reports/export/csv', name: 'app_report_export_csv')]
    #[Route('/reports/export', name: 'app_report_export')]
    public function exportCsv(Request $request, ReportService $reportService): StreamedResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Not authenticated');
        }

        $roleName = $user->getRoleEntity()?->getName();
        if ($roleName === 'ROLE_EMPLOYEE') {
            throw $this->createAccessDeniedException('Employees are not authorized to export reports.');
        }

        $preset = $request->query->get('date_preset', 'last_30_days');
        $customStart = $request->query->get('start_date');
        $customEnd = $request->query->get('end_date');
        $category = $request->query->get('category');
        $techId = $request->query->get('technician') ? (int) $request->query->get('technician') : null;

        [$startDate, $endDate] = $reportService->resolveDatePreset($preset, $customStart, $customEnd);
        $reportData = $reportService->getReportData($user, $startDate, $endDate, $category, $techId);

        $tickets = $reportData['tickets'];

        $response = new StreamedResponse(function () use ($tickets) {
            $handle = fopen('php://output', 'w+');
            // Add UTF-8 BOM for proper Excel rendering
            fwrite($handle, "\xEF\xBB\xBF");
            
            fputcsv($handle, [
                'Ticket Number', 
                'Title', 
                'Category', 
                'Priority', 
                'Status', 
                'Created By', 
                'Assigned To', 
                'Created At', 
                'Closed At'
            ]);

            foreach ($tickets as $ticket) {
                fputcsv($handle, [
                    $ticket->getTicketNumber(),
                    $ticket->getTitle(),
                    $ticket->getCategory(),
                    $ticket->getPriority(),
                    $ticket->getStatus(),
                    $ticket->getCreatedBy() ? $ticket->getCreatedBy()->getFirstName() . ' ' . $ticket->getCreatedBy()->getLastName() : 'Unknown',
                    $ticket->getAssignedTo() ? $ticket->getAssignedTo()->getFirstName() . ' ' . $ticket->getAssignedTo()->getLastName() : 'Unassigned',
                    $ticket->getCreatedAt()->format('Y-m-d H:i:s'),
                    $ticket->getClosedAt() ? $ticket->getClosedAt()->format('Y-m-d H:i:s') : 'N/A'
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="report-tickets-' . date('Ymd-His') . '.csv"');

        return $response;
    }

    #[Route('/reports/export/pdf', name: 'app_report_export_pdf')]
    public function exportPdf(Request $request, ReportService $reportService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Not authenticated');
        }

        $roleName = $user->getRoleEntity()?->getName();
        if ($roleName === 'ROLE_EMPLOYEE') {
            throw $this->createAccessDeniedException('Employees are not authorized to export reports.');
        }

        $preset = $request->query->get('date_preset', 'last_30_days');
        $customStart = $request->query->get('start_date');
        $customEnd = $request->query->get('end_date');
        $category = $request->query->get('category');
        $techId = $request->query->get('technician') ? (int) $request->query->get('technician') : null;

        [$startDate, $endDate] = $reportService->resolveDatePreset($preset, $customStart, $customEnd);
        $reportData = $reportService->getReportData($user, $startDate, $endDate, $category, $techId);

        $html = $this->renderView('report/pdf.html.twig', array_merge($reportData, [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'generatedAt' => date('Y-m-d H:i:s'),
            'category' => $category ?: 'All Categories',
        ]));

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="report-tickets-' . date('Ymd-His') . '.pdf"'
        ]);
    }
}
