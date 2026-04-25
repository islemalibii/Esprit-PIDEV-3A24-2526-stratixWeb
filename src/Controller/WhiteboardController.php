<?php

namespace App\Controller;

use App\Repository\TacheRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/whiteboard')]
class WhiteboardController extends AbstractController
{
    #[Route('/', name: 'app_whiteboard_index')]
    public function index(TacheRepository $tacheRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $toutesTaches = $tacheRepository->findAll();

        $tachesAFaire = [];
        $tachesEnCours = [];
        $tachesTerminees = [];

        foreach ($toutesTaches as $tache) {
            $employeId = $tache->getEmployeId();
            $employe = $employeId ? $utilisateurRepository->find($employeId) : null;
            $employeNom = $employe
                ? $employe->getPrenom() . ' ' . $employe->getNom()
                : 'Non assigné';

            $taskData = [
                'id'          => $tache->getId(),
                'titre'       => $tache->getTitre(),
                'description' => $tache->getDescription(),
                'deadline'    => $tache->getDeadline() ? $tache->getDeadline()->format('d/m/Y') : 'Non définie',
                'priorite'    => $tache->getPriorite(),
                'statut'      => $tache->getStatut(),
                'employeNom'  => $employeNom,
                'employeId'   => $tache->getEmployeId(),
            ];

            switch ($tache->getStatut()) {
                case 'A_FAIRE':   $tachesAFaire[]    = $taskData; break;
                case 'EN_COURS':  $tachesEnCours[]   = $taskData; break;
                case 'TERMINEE':  $tachesTerminees[] = $taskData; break;
            }
        }

        return $this->render('admin/whiteboard/index.html.twig', [
            'tachesAFaire'    => $tachesAFaire,
            'tachesEnCours'   => $tachesEnCours,
            'tachesTerminees' => $tachesTerminees,
            'countAFaire'     => count($tachesAFaire),
            'countEnCours'    => count($tachesEnCours),
            'countTerminees'  => count($tachesTerminees),
        ]);
    }

    #[Route('/move/{id}/{newStatut}', name: 'app_whiteboard_move', methods: ['POST'])]
    public function moveTask(int $id, string $newStatut, EntityManagerInterface $entityManager, TacheRepository $tacheRepository, Request $request): Response
    {
        $tache = $tacheRepository->find($id);

        if ($tache) {
            $tache->setStatut($newStatut);
            $entityManager->flush();
        }

        // Drag & drop AJAX request → return JSON
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        // Normal button click → redirect
        $this->addFlash('success', '✅ Tâche déplacée avec succès !');
        return $this->redirectToRoute('app_whiteboard_index');
    }

    #[Route('/delete/{id}', name: 'app_whiteboard_delete', methods: ['POST'])]
    public function deleteTask(int $id, EntityManagerInterface $entityManager, TacheRepository $tacheRepository, Request $request): Response
    {
        $tache = $tacheRepository->find($id);

        if ($tache) {
            $entityManager->remove($tache);
            $entityManager->flush();
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        $this->addFlash('success', '✅ Tâche supprimée avec succès !');
        return $this->redirectToRoute('app_whiteboard_index');
    }
}