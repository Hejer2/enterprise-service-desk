<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?RequestStack $requestStack = null
    ) {}

    /**
     * Log an action in the database activity log.
     */
    public function log(?User $user, string $action, ?string $entityName = null, ?int $entityId = null, ?array $details = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request ? $request->getClientIp() : null;

        $log = new ActivityLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setEntityName($entityName);
        $log->setEntityId($entityId);
        $log->setDetails($details);
        $log->setIpAddress($ipAddress);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
