<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(EntityManagerInterface $em): JsonResponse
    {
        $dbStatus = 'disconnected';
        $isHealthy = true;

        try {
            $connection = $em->getConnection();
            $connection->executeQuery('SELECT 1');
            $dbStatus = 'connected';
        } catch (\Throwable $e) {
            $isHealthy = false;
            $dbStatus = 'error: ' . $e->getMessage();
        }

        $statusCode = $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return $this->json([
            'status' => $isHealthy ? 'ok' : 'unhealthy',
            'database' => $dbStatus,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'environment' => $_ENV['APP_ENV'] ?? 'dev',
        ], $statusCode);
    }
}
