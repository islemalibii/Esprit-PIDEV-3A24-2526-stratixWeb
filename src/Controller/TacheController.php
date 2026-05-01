<?php
// src/Controller/TacheController.php

namespace App\Controller;

use App\Entity\Tache;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/tache')]
final class TacheController extends AbstractController
{
    #[Route(name: 'app_tache_index', methods: ['GET'])]
    public function index(Request $request, TacheRepository $tacheRepository): Response
    {
        // LIRE LES PARAMÈTRES DE RECHERCHE (FORCER LE TYPE STRING)
        $search = $request->query->get('search', '');
        $statut = $request->query->get('statut', '');
        $priorite = $request->query->get('priorite', '');
        
        // FORCER LES VALEURS EN STRING
        $search = is_string($search) ? $search : '';
        $statut = is_string($statut) ? $statut : '';
        $priorite = is_string($priorite) ? $priorite : '';

        // RECHERCHE RÉELLE EN BASE
        $taches = $tacheRepository->search($search, $statut, $priorite);

        // STATS SUR TOUTES LES TÂCHES
        $allTaches = $tacheRepository->findAll();
        $total = count($allTaches);
        $aFaire = $enCours = $terminees = $haute = $moyenne = $basse = 0;

        foreach ($allTaches as $t) {
            if ($t->getStatut() === 'A_FAIRE') $aFaire++;
            if ($t->getStatut() === 'EN_COURS') $enCours++;
            if ($t->getStatut() === 'TERMINEE') $terminees++;
            if ($t->getPriorite() === 'HAUTE') $haute++;
            if ($t->getPriorite() === 'MOYENNE') $moyenne++;
            if ($t->getPriorite() === 'BASSE') $basse++;
        }

        $tauxCompletion = $total > 0 ? round(($terminees / $total) * 100, 1) : 0;

        $alertes = [
            ['employe' => 'Jean Dupont', 'taches_en_cours' => 4, 'niveau' => 'élevé'],
            ['employe' => 'Marie Martin', 'taches_en_cours' => 7, 'niveau' => 'critique'],
            ['employe' => 'Pierre Durand', 'taches_en_cours' => 2, 'niveau' => 'élevé'],
        ];

        return $this->render('admin/tache/index.html.twig', [
            'taches' => $taches,
            'total' => $total,
            'a_faire' => $aFaire,
            'en_cours' => $enCours,
            'terminees' => $terminees,
            'haute' => $haute,
            'moyenne' => $moyenne,
            'basse' => $basse,
            'taux_completion' => $tauxCompletion,
            'alertes' => $alertes,
            'search' => $search,
            'statut' => $statut,
            'priorite' => $priorite,
        ]);
    }

    #[Route('/new', name: 'app_tache_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tache);
            $entityManager->flush();
            $this->addFlash('success', '✅ Tâche ajoutée avec succès !');
            return $this->redirectToRoute('app_tache_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tache/new.html.twig', [
            'tache' => $tache,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tache_show', methods: ['GET'])]
    public function show(Tache $tache): Response
    {
        return $this->render('admin/tache/show.html.twig', [
            'tache' => $tache,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tache_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tache $tache, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', '✅ Tâche modifiée avec succès !');
            return $this->redirectToRoute('app_tache_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tache/edit.html.twig', [
            'tache' => $tache,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tache_delete', methods: ['POST'])]
    public function delete(Request $request, Tache $tache, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if ($token !== null && is_string($token) && $this->isCsrfTokenValid('delete' . $tache->getId(), $token)) {
            $entityManager->remove($tache);
            $entityManager->flush();
            $this->addFlash('success', '✅ Tâche supprimée avec succès !');
        } else {
            $this->addFlash('danger', '❌ Erreur lors de la suppression !');
        }

        return $this->redirectToRoute('app_tache_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/stats/data', name: 'app_tache_stats', methods: ['GET'])]
    public function stats(TacheRepository $tacheRepository): Response
    {
        $taches = $tacheRepository->findAll();
        $aFaire = $enCours = $terminees = 0;
        
        foreach ($taches as $t) {
            $statut = $t->getStatut();
            if ($statut === 'A_FAIRE') $aFaire++;
            if ($statut === 'EN_COURS') $enCours++;
            if ($statut === 'TERMINEE') $terminees++;
        }
        
        return $this->json([
            'a_faire' => $aFaire,
            'en_cours' => $enCours,
            'terminees' => $terminees,
            'total' => count($taches),
        ]);
    }
}