<?php

namespace App\Service;

use App\Entity\AutomationExecution;
use App\Entity\AutomationRule;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\AutomationExecutionRepository;
use App\Repository\AutomationRuleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AutomationEngineService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AutomationRuleRepository $ruleRepository,
        private AutomationExecutionRepository $executionRepository,
        private UserRepository $userRepository,
        private TicketActivityLogger $activityLogger,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {}

    public function processEvent(string $triggerType, Ticket $ticket, array $context = []): array
    {
        $rules = $this->ruleRepository->findActiveByTrigger($triggerType);
        $results = ['evaluated' => count($rules), 'executed' => 0, 'skipped' => 0];

        foreach ($rules as $rule) {
            $executionKey = sprintf('rule_%d_ticket_%d_%s', $rule->getId(), $ticket->getId(), md5($triggerType . json_encode($context)));

            // Deduplication guard
            $existing = $this->executionRepository->findOneBy(['executionKey' => $executionKey]);
            if ($existing) {
                $results['skipped']++;
                continue;
            }

            if ($this->evaluateConditions($rule->getConditions(), $ticket, $context)) {
                $actionsExecuted = $this->executeActions($rule->getActions(), $ticket, $rule);

                $execution = new AutomationExecution();
                $execution->setRule($rule);
                $execution->setTicket($ticket);
                $execution->setStatus('SUCCESS');
                $execution->setActionsExecuted($actionsExecuted);
                $execution->setExecutionKey($executionKey);

                $this->entityManager->persist($execution);
                $this->entityManager->flush();

                $results['executed']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    private function evaluateConditions(array $conditions, Ticket $ticket, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $field => $targetValue) {
            switch ($field) {
                case 'priority':
                    if ($ticket->getPriority() !== $targetValue) return false;
                    break;
                case 'status':
                    if ($ticket->getStatus() !== $targetValue) return false;
                    break;
                case 'category':
                    if ($ticket->getCategory() !== $targetValue) return false;
                    break;
                case 'assigned':
                    $isAssigned = $ticket->getAssignedTo() !== null;
                    if (filter_var($targetValue, FILTER_VALIDATE_BOOLEAN) !== $isAssigned) return false;
                    break;
                case 'createdByRole':
                    $role = $ticket->getCreatedBy()?->getRoleEntity()?->getName();
                    if ($role !== $targetValue) return false;
                    break;
            }
        }

        return true;
    }

    private function executeActions(array $actions, Ticket $ticket, AutomationRule $rule): array
    {
        $executed = [];

        foreach ($actions as $action) {
            $type = $action['type'] ?? '';
            $value = $action['value'] ?? null;

            switch ($type) {
                case 'ASSIGN_TECHNICIAN':
                    $tech = null;
                    if ($value === 'AUTO_LOAD_BALANCED') {
                        $tech = $this->findLowestWorkloadTechnician($ticket->getCategory());
                    } elseif (is_numeric($value)) {
                        $tech = $this->userRepository->find((int)$value);
                    }

                    if ($tech) {
                        $ticket->setAssignedTo($tech);
                        if ($ticket->getStatus() === 'Open') {
                            $ticket->setStatus('In Progress');
                        }
                        $this->activityLogger->logActivity($ticket, null, 'ticket_auto_assigned', "Automated assignment via rule: {$rule->getName()}", 'system', [
                            'ruleId' => $rule->getId(),
                            'assignedTo' => $tech->getFullName(),
                        ]);
                        $this->notificationService->notify($tech, 'Ticket Assigned', "Ticket #{$ticket->getTicketNumber()} automatically assigned via automation rule.", 'ticket_assigned', $ticket->getId());
                        $executed[] = ['action' => 'ASSIGN_TECHNICIAN', 'assignee' => $tech->getFullName()];
                    }
                    break;

                case 'CHANGE_PRIORITY':
                    if ($value && $ticket->getPriority() !== $value) {
                        $prev = $ticket->getPriority();
                        $ticket->setPriority($value);
                        $this->activityLogger->logActivity($ticket, null, 'priority_changed', "Priority changed from $prev to $value by automation rule: {$rule->getName()}", 'system');
                        $executed[] = ['action' => 'CHANGE_PRIORITY', 'from' => $prev, 'to' => $value];
                    }
                    break;

                case 'CHANGE_STATUS':
                    if ($value && $ticket->getStatus() !== $value) {
                        $prev = $ticket->getStatus();
                        $ticket->setStatus($value);
                        $this->activityLogger->logActivity($ticket, null, 'status_changed', "Status changed from $prev to $value by automation rule: {$rule->getName()}", 'system');
                        $executed[] = ['action' => 'CHANGE_STATUS', 'from' => $prev, 'to' => $value];
                    }
                    break;

                case 'SEND_NOTIFICATION':
                    if ($ticket->getCreatedBy()) {
                        $this->notificationService->notify($ticket->getCreatedBy(), 'Automation Alert', $value ?: "Automation rule {$rule->getName()} executed.", 'system', $ticket->getId());
                        $executed[] = ['action' => 'SEND_NOTIFICATION', 'recipient' => $ticket->getCreatedBy()->getEmail()];
                    }
                    break;
            }
        }

        $this->entityManager->flush();
        return $executed;
    }

    public function findLowestWorkloadTechnician(?string $category = null): ?User
    {
        $roleName = 'ROLE_IT_TECH';
        if ($category === 'Machine Maintenance') {
            $roleName = 'ROLE_MAINTENANCE_TECH';
        } elseif ($category === 'Leave Request & HR') {
            $roleName = 'ROLE_HR';
        }

        $techs = $this->userRepository->findByRoleName($roleName);
        if (empty($techs)) {
            $techs = $this->userRepository->findByRoleName('ROLE_IT_TECH');
        }

        if (empty($techs)) {
            return null;
        }

        $lowestTech = null;
        $minOpenCount = PHP_INT_MAX;

        foreach ($techs as $tech) {
            $openCount = (int) $this->entityManager->createQueryBuilder()
                ->select('COUNT(t.id)')
                ->from(Ticket::class, 't')
                ->where('t.assignedTo = :tech')
                ->andWhere('t.status NOT IN (:closed)')
                ->setParameter('tech', $tech)
                ->setParameter('closed', ['Resolved', 'Closed'])
                ->getQuery()
                ->getSingleScalarResult();

            if ($openCount < $minOpenCount) {
                $minOpenCount = $openCount;
                $lowestTech = $tech;
            }
        }

        return $lowestTech;
    }
}
