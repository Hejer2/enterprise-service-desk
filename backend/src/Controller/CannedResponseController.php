<?php

namespace App\Controller;

use App\Entity\CannedResponse;
use App\Entity\User;
use App\Repository\CannedResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/canned-responses')]
#[IsGranted('ROLE_IT_TECH')]
class CannedResponseController extends AbstractController
{
    #[Route('', name: 'app_canned_response_index', methods: ['GET', 'POST'])]
    public function index(Request $request, CannedResponseRepository $repo, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $role = $user->getRoleEntity()?->getName();

        if ($role === 'ROLE_EMPLOYEE') {
            throw $this->createAccessDeniedException('Access denied for employees.');
        }

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $category = $request->request->get('category');
            $content = $request->request->get('content');

            if ($title && $content) {
                $canned = new CannedResponse();
                $canned->setTitle(trim($title));
                $canned->setCategory(trim($category));
                $canned->setContent(trim($content));
                $canned->setCreatedBy($user);

                $em->persist($canned);
                $em->flush();

                $this->addFlash('success', 'Canned response template created.');
                return $this->redirectToRoute('app_canned_response_index');
            }
        }

        $responses = $repo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('canned_responses/index.html.twig', [
            'responses' => $responses,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_canned_response_delete', methods: ['POST'])]
    public function delete(int $id, CannedResponseRepository $repo, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $role = $user->getRoleEntity()?->getName();

        if ($role === 'ROLE_EMPLOYEE') {
            throw $this->createAccessDeniedException('Access denied for employees.');
        }

        $canned = $repo->find($id);
        if ($canned) {
            $em->remove($canned);
            $em->flush();
            $this->addFlash('success', 'Canned response template deleted.');
        }

        return $this->redirectToRoute('app_canned_response_index');
    }
}
