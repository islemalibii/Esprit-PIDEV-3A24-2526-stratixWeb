<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Form\PlanningType;
use App\Repository\PlanningRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/planning')]
final class PlanningController extends AbstractController
{
    #[Route(name: 'app_planning_index', methods: ['GET'])]
    public function index(PlanningRepository $planningRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $plannings = $planningRepository->findAll();

        $employes = [];
        foreach ($utilisateurRepository->findAll() as $u) {
            $employes[$u->getId()] = $u->getPrenom() . ' ' . $u->getNom();
        }

        return $this->render('admin/planning/index.html.twig', [
            'plannings' => $plannings,
            'employes'  => $employes,
        ]);
    }

    #[Route('/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $planning = new Planning();
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validation manuelle
            $errors = $validator->validate($planning);
            
            if (count($errors) === 0) {
                $entityManager->persist($planning);
                $entityManager->flush();
                $this->addFlash('success', '✅ Planning ajouté avec succès !');
                return $this->redirectToRoute('app_planning_index');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }
        }

        return $this->render('admin/planning/new.html.twig', [
            'planning' => $planning,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_planning_show', methods: ['GET'])]
    public function show(Planning $planning, UtilisateurRepository $utilisateurRepository): Response
    {
        $employes = [];
        foreach ($utilisateurRepository->findAll() as $u) {
            $employes[$u->getId()] = $u->getPrenom() . ' ' . $u->getNom();
        }

        return $this->render('admin/planning/show.html.twig', [
            'planning' => $planning,
            'employes' => $employes,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_planning_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Planning $planning, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($planning);
            
            if (count($errors) === 0) {
                $entityManager->flush();
                $this->addFlash('success', '✅ Planning modifié avec succès !');
                return $this->redirectToRoute('app_planning_index');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }
        }

        return $this->render('admin/planning/edit.html.twig', [
            'planning' => $planning,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_planning_delete', methods: ['POST'])]
    public function delete(Request $request, Planning $planning, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $planning->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($planning);
            $entityManager->flush();
            $this->addFlash('success', '✅ Planning supprimé avec succès !');
        } else {
            $this->addFlash('danger', '❌ Erreur lors de la suppression !');
        }

        return $this->redirectToRoute('app_planning_index');
    }
}