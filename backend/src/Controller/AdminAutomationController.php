<?php

namespace App\Controller;

use App\Entity\AutomationExecution;
use App\Entity\AutomationRule;
use App\Entity\RecurringTicket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/admin/automation')]
class AdminAutomationController extends AbstractController
{
    private function checkAdminAccess(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleEntity()?->getName() !== 'ROLE_ADMIN') {
            throw new AccessDeniedException('Administrator access required.');
        }
        return $user;
    }

    #[Route('', name: 'app_admin_automation_index', methods: ['GET'])]
    #[Route('/rules', name: 'app_admin_automation_rules', methods: ['GET'])]
    public function rules(EntityManagerInterface $em): Response
    {
        $this->checkAdminAccess();
        $rules = $em->getRepository(AutomationRule::class)->findBy([], ['priority' => 'DESC']);
        $executions = $em->getRepository(AutomationExecution::class)->findBy([], ['executedAt' => 'DESC'], 20);

        return $this->render('automation/admin/index.html.twig', [
            'rules' => $rules,
            'executions' => $executions,
        ]);
    }

    #[Route('/rules/{id}/toggle', name: 'app_admin_automation_rule_toggle', methods: ['POST'])]
    public function toggle(AutomationRule $rule, EntityManagerInterface $em): Response
    {
        $this->checkAdminAccess();
        $rule->setIsActive(!$rule->isIsActive());
        $em->flush();

        $this->addFlash('success', sprintf('Automation Rule "%s" is now %s.', $rule->getName(), $rule->isIsActive() ? 'ACTIVE' : 'INACTIVE'));
        return $this->redirectToRoute('app_admin_automation_rules');
    }

    #[Route('/recurring', name: 'app_admin_automation_recurring', methods: ['GET'])]
    public function recurring(EntityManagerInterface $em): Response
    {
        $this->checkAdminAccess();
        $recurring = $em->getRepository(RecurringTicket::class)->findBy([], ['nextRunAt' => 'ASC']);

        return $this->render('automation/admin/recurring.html.twig', [
            'recurring' => $recurring,
        ]);
    }
}
