<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ExecutiveAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/executive-analytics')]
class ApiExecutiveAnalyticsController extends AbstractController
{
    #[Route('', name: 'api_executive_analytics_index', methods: ['GET'])]
    public function index(Request $request, ExecutiveAnalyticsService $analyticsService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $role = $user->getRoleEntity()?->getName();
        if ($role !== 'ROLE_ADMIN' && $role !== 'ROLE_HR') {
            return $this->json(['error' => 'Forbidden: Executive analytics access required.'], Response::HTTP_FORBIDDEN);
        }

        $filters = [
            'preset' => $request->query->get('preset', '30_days'),
            'from' => $request->query->get('from'),
            'to' => $request->query->get('to'),
            'category' => $request->query->get('category'),
            'priority' => $request->query->get('priority'),
            'status' => $request->query->get('status'),
            'technician' => $request->query->get('technician'),
        ];
        $compare = filter_var($request->query->get('compare', 'true'), FILTER_VALIDATE_BOOLEAN);

        $data = $analyticsService->getAnalyticsData($filters, $compare);

        return $this->json($data);
    }
}
