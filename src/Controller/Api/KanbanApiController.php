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
            $deadline = $tache->getDeadline();
            $tacheData = [
                'id' => $tache->getId(),
                'titre' => $tache->getTitre(),
                'description' => $tache->getDescription(),
                'priorite' => $tache->getPriorite(),
                'deadline' => $deadline ? $deadline->format('Y-m-d') : null,
                'statut' => $tache->getStatut(),
                'estEnRetard' => $deadline && $deadline < new \DateTime() && $tache->getStatut() !== 'TERMINEE',
            ];
            
            $statut = $tache->getStatut();
            if ($statut === 'A_FAIRE') {
                $aFaire[] = $tacheData;
            } elseif ($statut === 'EN_COURS') {
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
        
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }
        
        $content = $request->getContent();
        $data = json_decode($content, true);
        
        $newStatus = null;
        if (is_array($data) && isset($data['status'])) {
            $newStatus = $data['status'];
        }
        
        $tache = $tacheRepository->find($id);
        
        if (!$tache || $tache->getEmployeId() !== $user->getId()) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }
        
        $statusMap = [
            'a_faire' => 'A_FAIRE',
            'en_cours' => 'EN_COURS', 
            'terminees' => 'TERMINEE'
        ];
        
        // Correction ligne 114 - vérifier que la clé existe
        if (is_string($newStatus) && isset($statusMap[$newStatus])) {
            $newStatus = $statusMap[$newStatus];
        }
        
        if (!is_string($newStatus) || !in_array($newStatus, ['A_FAIRE', 'EN_COURS', 'TERMINEE'], true)) {
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