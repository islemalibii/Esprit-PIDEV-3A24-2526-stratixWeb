<?php
// src/Controller/Api/CalendarApiController.php

namespace App\Controller\Api;

use App\Entity\Tache;
use App\Entity\Planning;
use App\Repository\TacheRepository;
use App\Repository\PlanningRepository;
use App\Repository\UserBadgeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/calendar')]
#[IsGranted('ROLE_USER')]
class CalendarApiController extends AbstractController
{
    #[Route('/events', name: 'api_calendar_events', methods: ['GET'])]
    public function getEvents(
        Request $request,
        TacheRepository $tacheRepository,
        PlanningRepository $planningRepository
    ): JsonResponse {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        
        if (!$userId) {
            return $this->json(['success' => false, 'events' => [], 'total' => 0]);
        }
        
        // Paramètres de filtrage
        $startDate = $request->query->get('start');
        $endDate = $request->query->get('end');
        $type = $request->query->get('type', 'all');
        $priority = $request->query->get('priority', 'all');
        
        // Récupérer les tâches
        $taches = $tacheRepository->findBy(['employeId' => $userId]);
        $plannings = $planningRepository->findAll();
        
        $events = [];
        
        // Convertir les tâches en événements
        foreach ($taches as $tache) {
            if ($tache->getDeadline()) {
                $dateStr = $tache->getDeadline()->format('Y-m-d');
                
                // Filtrer par date
                if ($startDate && $dateStr < $startDate) continue;
                if ($endDate && $dateStr > $endDate) continue;
                if ($type !== 'all' && $type !== 'tache') continue;
                if ($priority !== 'all' && $tache->getPriorite() !== $priority) continue;
                
                // Icône selon statut
                $icon = match($tache->getStatut()) {
                    'A_FAIRE' => '📌',
                    'EN_COURS' => '⚡',
                    'TERMINEE' => '✅',
                    default => '📋'
                };
                
                // Couleur selon priorité
                $color = match($tache->getPriorite()) {
                    'HAUTE' => '#dc2626',
                    'MOYENNE' => '#f59e0b',
                    default => '#10b981'
                };
                
                $events[] = [
                    'id' => 'tache_' . $tache->getId(),
                    'title' => $icon . ' ' . $tache->getTitre(),
                    'start' => $dateStr,
                    'end' => $dateStr,
                    'type' => 'tache',
                    'priority' => $tache->getPriorite(),
                    'color' => $color,
                    'status' => $tache->getStatut(),
                    'description' => $tache->getDescription(),
                    'url' => $this->generateUrl('app_tache_show', ['id' => $tache->getId()]),
                ];
            }
        }
        
        // Convertir les plannings en événements
        foreach ($plannings as $planning) {
            $datePlanning = null;
            $titrePlanning = 'Planning';
            $descriptionPlanning = '';
            
            if (method_exists($planning, 'getDate')) {
                $datePlanning = $planning->getDate();
            } elseif (method_exists($planning, 'getDateDebut')) {
                $datePlanning = $planning->getDateDebut();
            }
            
            if (method_exists($planning, 'getTitre')) {
                $titrePlanning = $planning->getTitre();
            } elseif (method_exists($planning, 'getTypeShift')) {
                $titrePlanning = $planning->getTypeShift();
            } elseif (method_exists($planning, 'getNom')) {
                $titrePlanning = $planning->getNom();
            }
            
            if (method_exists($planning, 'getDescription')) {
                $descriptionPlanning = $planning->getDescription();
            }
            
            if ($datePlanning) {
                $dateStr = $datePlanning->format('Y-m-d');
                
                if ($startDate && $dateStr < $startDate) continue;
                if ($endDate && $dateStr > $endDate) continue;
                if ($type !== 'all' && $type !== 'planning') continue;
                
                $events[] = [
                    'id' => 'planning_' . $planning->getId(),
                    'title' => '📅 ' . $titrePlanning,
                    'start' => $dateStr,
                    'type' => 'planning',
                    'color' => '#3b82f6',
                    'description' => $descriptionPlanning,
                ];
            }
        }
        
        // Statistiques
        $total = count($taches);
        $terminees = count(array_filter($taches, fn($t) => $t->getStatut() === 'TERMINEE'));
        $enCours = count(array_filter($taches, fn($t) => $t->getStatut() === 'EN_COURS'));
        $aFaire = count(array_filter($taches, fn($t) => $t->getStatut() === 'A_FAIRE'));
        
        return $this->json([
            'success' => true,
            'events' => $events,
            'total' => count($events),
            'stats' => [
                'total_taches' => $total,
                'terminees' => $terminees,
                'en_cours' => $enCours,
                'a_faire' => $aFaire,
                'completion_rate' => $total > 0 ? round(($terminees / $total) * 100) : 0,
            ]
        ]);
    }
    
    #[Route('/events/{id}', name: 'api_calendar_event_update', methods: ['PUT'])]
    public function updateEvent(
        string $id,
        Request $request,
        TacheRepository $tacheRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }
        
        // Extraire l'ID réel et le type
        if (str_starts_with($id, 'tache_')) {
            $tacheId = (int) str_replace('tache_', '', $id);
            $tache = $tacheRepository->find($tacheId);
            
            if (!$tache || $tache->getEmployeId() !== $user->getId()) {
                return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
            }
            
            if (isset($data['start'])) {
                $newDate = new \DateTime($data['start']);
                $tache->setDeadline($newDate);
                $em->flush();
                return $this->json(['success' => true, 'message' => 'Date mise à jour']);
            }
            
            if (isset($data['status'])) {
                $tache->setStatut($data['status']);
                $em->flush();
                return $this->json(['success' => true, 'message' => 'Statut mis à jour']);
            }
        }
        
        return $this->json(['success' => false, 'error' => 'Type non supporté'], 400);
    }
    
    #[Route('/stats', name: 'api_calendar_stats', methods: ['GET'])]
    public function getStats(
        TacheRepository $tacheRepository
    ): JsonResponse {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        
        if (!$userId) {
            return $this->json(['success' => true, 'stats' => [
                'total' => 0, 'terminees' => 0, 'en_cours' => 0, 'a_faire' => 0, 'completion_rate' => 0
            ]]);
        }
        
        $taches = $tacheRepository->findBy(['employeId' => $userId]);
        
        $total = count($taches);
        $terminees = count(array_filter($taches, fn($t) => $t->getStatut() === 'TERMINEE'));
        $enCours = count(array_filter($taches, fn($t) => $t->getStatut() === 'EN_COURS'));
        $aFaire = count(array_filter($taches, fn($t) => $t->getStatut() === 'A_FAIRE'));
        
        // Tâches par priorité
        $haute = count(array_filter($taches, fn($t) => $t->getPriorite() === 'HAUTE'));
        $moyenne = count(array_filter($taches, fn($t) => $t->getPriorite() === 'MOYENNE'));
        $basse = count(array_filter($taches, fn($t) => $t->getPriorite() === 'BASSE'));
        
        return $this->json([
            'success' => true,
            'stats' => [
                'total' => $total,
                'terminees' => $terminees,
                'en_cours' => $enCours,
                'a_faire' => $aFaire,
                'completion_rate' => $total > 0 ? round(($terminees / $total) * 100) : 0,
                'priorites' => [
                    'haute' => $haute,
                    'moyenne' => $moyenne,
                    'basse' => $basse,
                ],
            ]
        ]);
    }
}