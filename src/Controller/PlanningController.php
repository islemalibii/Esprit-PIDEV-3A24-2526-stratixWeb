<?php
// src/Controller/PlanningController.php

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
    public function index(Request $request, PlanningRepository $planningRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        // ========== RECHERCHE PHP (côté serveur) ==========
        $searchDate = $request->query->get('search_date', '');
        $searchType = $request->query->get('search_type', '');
        $searchEmploye = $request->query->get('search_employe', '');
        
        // Construire la requête avec filtres
        $qb = $planningRepository->createQueryBuilder('p');
        
        // Filtre par date
        if (!empty($searchDate)) {
            $date = new \DateTime($searchDate);
            $qb->andWhere('p.date = :date')
               ->setParameter('date', $date);
        }
        
        // Filtre par type de shift
        if (!empty($searchType)) {
            $qb->andWhere('p.typeShift = :type')
               ->setParameter('type', $searchType);
        }
        
        // Filtre par employé
        if (!empty($searchEmploye)) {
            $qb->andWhere('p.employeId = :employe')
               ->setParameter('employe', (int)$searchEmploye);
        }
        
        // Trier par date décroissante
        $qb->orderBy('p.date', 'DESC');
        
        $plannings = $qb->getQuery()->getResult();
        
        // Récupérer tous les employés
        $employes = [];
        foreach ($utilisateurRepository->findAll() as $u) {
            $employes[$u->getId()] = $u->getPrenom() . ' ' . $u->getNom();
        }
        
        return $this->render('admin/planning/index.html.twig', [
            'plannings' => $plannings,
            'employes' => $employes,
            'search_date' => $searchDate,
            'search_type' => $searchType,
            'search_employe' => $searchEmploye,
        ]);
    }

    #[Route('/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $planning = new Planning();
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($planning);
            $entityManager->flush();
            $this->addFlash('success', '✅ Planning ajouté avec succès !');
            return $this->redirectToRoute('app_planning_index');
        }

        return $this->render('admin/planning/new.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
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

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', '✅ Planning modifié avec succès !');
            return $this->redirectToRoute('app_planning_index');
        }

        return $this->render('admin/planning/edit.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
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