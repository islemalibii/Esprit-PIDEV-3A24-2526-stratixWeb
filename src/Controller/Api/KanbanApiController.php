<?php
// src/Controller/Api/KanbanApiController.php

namespace App\Controller\Api;

use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/kanban')]
#[IsGranted('ROLE_USER')]
class KanbanApiController extends AbstractController
{
    #[Route('/taches', name: 'api_kanban_taches', methods: ['GET'])]
    public function getTachesKanban(TacheRepository $tacheRepository): JsonResponse
    {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        
        if (!$userId) {
            return $this->json(['success' => false, 'colonnes' => ['a_faire' => [], 'en_cours' => [], 'terminees' => []]]);
        }
        
        $taches = $tacheRepository->findBy(['employeId' => $userId]);
        
        $aFaire = [];
        $enCours = [];
        $terminees = [];
        
        foreach ($taches as $tache) {
            $tacheData = [
                'id' => $tache->getId(),
                'titre' => $tache->getTitre(),
                'description' => $tache->getDescription(),
                'priorite' => $tache->getPriorite(),
                'deadline' => $tache->getDeadline() ? $tache->getDeadline()->format('Y-m-d') : null,
                'statut' => $tache->getStatut(),
                'estEnRetard' => $tache->getDeadline() && $tache->getDeadline() < new \DateTime() && $tache->getStatut() !== 'TERMINEE',
            ];
            
            if ($tache->getStatut() === 'A_FAIRE') {
                $aFaire[] = $tacheData;
            } elseif ($tache->getStatut() === 'EN_COURS') {
                $enCours[] = $tacheData;
            } else {
                $terminees[] = $tacheData;
            }
        }
        
        $total = count($taches);
        $termineesCount = count($terminees);
        $completionRate = $total > 0 ? round(($termineesCount / $total) * 100) : 0;
        
        return $this->json([
            'success' => true,
            'colonnes' => [
                'a_faire' => $aFaire,
                'en_cours' => $enCours,
                'terminees' => $terminees
            ],
            'stats' => [
                'total' => $total,
                'terminees' => $termineesCount,
                'en_cours' => count($enCours),
                'a_faire' => count($aFaire),
                'completion_rate' => $completionRate,
                'taches_en_retard' => count(array_filter($taches, fn($t) => 
                    $t->getDeadline() && $t->getDeadline() < new \DateTime() && $t->getStatut() !== 'TERMINEE'
                ))
            ]
        ]);
    }
    
    #[Route('/tache/{id}/move', name: 'api_kanban_move', methods: ['PUT'])]
    public function moveTache(
        int $id,
        Request $request,
        TacheRepository $tacheRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;
        
        $tache = $tacheRepository->find($id);
        
        if (!$tache || $tache->getEmployeId() !== $user->getId()) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }
        
        $statusMap = [
            'a_faire' => 'A_FAIRE',
            'en_cours' => 'EN_COURS', 
            'terminees' => 'TERMINEE'
        ];
        
        $newStatus = $statusMap[$newStatus] ?? $newStatus;
        
        if (!in_array($newStatus, ['A_FAIRE', 'EN_COURS', 'TERMINEE'])) {
            return $this->json(['success' => false, 'error' => 'Statut invalide'], 400);
        }
        
        $tache->setStatut($newStatus);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Tâche déplacée avec succès',
            'nouveau_statut' => $newStatus
        ]);
    }
}