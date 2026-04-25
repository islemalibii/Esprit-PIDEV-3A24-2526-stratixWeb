<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use App\Repository\UtilisateurRepository;
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
    public function index(TacheRepository $tacheRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $taches = $tacheRepository->findAll();
        
        $total = count($taches);
        $aFaire = 0;
        $enCours = 0;
        $terminees = 0;
        $haute = 0;
        $moyenne = 0;
        $basse = 0;
        
        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'A_FAIRE') $aFaire++;
            if ($tache->getStatut() === 'EN_COURS') $enCours++;
            if ($tache->getStatut() === 'TERMINEE') $terminees++;
            if ($tache->getPriorite() === 'HAUTE') $haute++;
            if ($tache->getPriorite() === 'MOYENNE') $moyenne++;
            if ($tache->getPriorite() === 'BASSE') $basse++;
        }
        
        // Taux de completion
        $tauxCompletion = $total > 0 ? round(($terminees / $total) * 100, 1) : 0;
        
        // ============ ALERTES - Version TEST avec données forcées ============
        // Pour tester, on force l'affichage des alertes
        $alertes = [
            ['employe' => 'Jean Dupont', 'taches_en_cours' => 4, 'niveau' => 'élevé'],
            ['employe' => 'Marie Martin', 'taches_en_cours' => 7, 'niveau' => 'critique'],
            ['employe' => 'Pierre Durand', 'taches_en_cours' => 2, 'niveau' => 'élevé']
        ];
        
        // Version réelle (à décommenter quand les alertes fonctionnent)
        /*
        $employes = $utilisateurRepository->findAll();
        $alertes = [];
        foreach ($employes as $e) {
            $nbTaches = 0;
            foreach ($taches as $t) {
                if ($t->getEmployeId() === $e->getId() && $t->getStatut() !== 'TERMINEE') {
                    $nbTaches++;
                }
            }
            
            if ($nbTaches > 0) {
                $alertes[] = [
                    'employe' => $e->getPrenom() . ' ' . $e->getNom(),
                    'taches_en_cours' => $nbTaches,
                    'niveau' => $nbTaches > 5 ? 'critique' : 'élevé'
                ];
            }
        }
        */
        
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
        ]);
    }

    #[Route('/new', name: 'app_tache_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($tache);
            
            if (count($errors) === 0 && $form->isValid()) {
                $entityManager->persist($tache);
                $entityManager->flush();

                $this->addFlash('success', '✅ Tâche ajoutée avec succès !');
                return $this->redirectToRoute('app_tache_index', [], Response::HTTP_SEE_OTHER);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }
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
    public function edit(Request $request, Tache $tache, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($tache);
            
            if (count($errors) === 0 && $form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', '✅ Tâche modifiée avec succès !');
                return $this->redirectToRoute('app_tache_index', [], Response::HTTP_SEE_OTHER);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }
        }

        return $this->render('admin/tache/edit.html.twig', [
            'tache' => $tache,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tache_delete', methods: ['POST'])]
    public function delete(Request $request, Tache $tache, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tache->getId(), $request->getPayload()->getString('_token'))) {
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
        
        $aFaire = 0;
        $enCours = 0;
        $terminees = 0;
        
        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'A_FAIRE') $aFaire++;
            if ($tache->getStatut() === 'EN_COURS') $enCours++;
            if ($tache->getStatut() === 'TERMINEE') $terminees++;
        }
        
        return $this->json([
            'a_faire' => $aFaire,
            'en_cours' => $enCours,
            'terminees' => $terminees,
            'total' => count($taches),
        ]);
    }
}