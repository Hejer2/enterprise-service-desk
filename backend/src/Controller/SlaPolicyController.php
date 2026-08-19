<?php

namespace App\Controller;

use App\Entity\SlaPolicy;
use App\Repository\SlaPolicyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sla')]
#[IsGranted('ROLE_ADMIN')]
class SlaPolicyController extends AbstractController
{
    #[Route('', name: 'app_admin_sla_index', methods: ['GET'])]
    public function index(SlaPolicyRepository $slaPolicyRepository): Response
    {
        $policies = $slaPolicyRepository->findBy([], ['priority' => 'ASC']);

        return $this->render('sla/index.html.twig', [
            'policies' => $policies,
        ]);
    }

    #[Route('', name: 'app_admin_sla_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $name = $request->request->get('name');
        $priority = $request->request->get('priority');
        $firstResponse = (int) $request->request->get('firstResponseMinutes');
        $resolution = (int) $request->request->get('resolutionMinutes');
        $warning = (int) $request->request->get('warningPercentage');

        if ($name && $priority && $firstResponse > 0 && $resolution > 0 && $warning > 0 && $warning < 100) {
            $policy = new SlaPolicy();
            $policy->setName($name);
            $policy->setPriority($priority);
            $policy->setFirstResponseMinutes($firstResponse);
            $policy->setResolutionMinutes($resolution);
            $policy->setWarningPercentage($warning);
            $policy->setIsActive(true);

            $em->persist($policy);
            $em->flush();

            $this->addFlash('success', 'SLA Policy created successfully.');
        } else {
            $this->addFlash('error', 'Invalid SLA policy input parameters.');
        }

        return $this->redirectToRoute('app_admin_sla_index');
    }

    #[Route('/{id}/edit', name: 'app_admin_sla_edit', methods: ['POST'])]
    public function edit(SlaPolicy $policy, Request $request, EntityManagerInterface $em): Response
    {
        $name = $request->request->get('name');
        $firstResponse = (int) $request->request->get('firstResponseMinutes');
        $resolution = (int) $request->request->get('resolutionMinutes');
        $warning = (int) $request->request->get('warningPercentage');

        if ($name && $firstResponse > 0 && $resolution > 0 && $warning > 0 && $warning < 100) {
            $policy->setName($name);
            $policy->setFirstResponseMinutes($firstResponse);
            $policy->setResolutionMinutes($resolution);
            $policy->setWarningPercentage($warning);

            $em->flush();
            $this->addFlash('success', 'SLA Policy updated successfully.');
        } else {
            $this->addFlash('error', 'Invalid parameters provided for SLA Policy update.');
        }

        return $this->redirectToRoute('app_admin_sla_index');
    }

    #[Route('/{id}/toggle', name: 'app_admin_sla_toggle', methods: ['POST'])]
    public function toggle(SlaPolicy $policy, EntityManagerInterface $em): Response
    {
        $policy->setIsActive(!$policy->isIsActive());
        $em->flush();

        $this->addFlash('success', sprintf('SLA Policy for %s is now %s.', $policy->getPriority(), $policy->isIsActive() ? 'Active' : 'Disabled'));

        return $this->redirectToRoute('app_admin_sla_index');
    }
}
