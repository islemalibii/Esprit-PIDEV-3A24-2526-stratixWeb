<?php

namespace App\Controller;

use App\Repository\PlanningRepository;
use App\Repository\TacheRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/calendar')]
class CalendarController extends AbstractController
{
    private UtilisateurRepository $utilisateurRepository;
    
    public function __construct(UtilisateurRepository $utilisateurRepository)
    {
        $this->utilisateurRepository = $utilisateurRepository;
    }
    
    #[Route('/', name: 'app_calendar_index')]
    public function index(PlanningRepository $planningRepository, TacheRepository $tacheRepository): Response
    {
        $plannings = $planningRepository->findAll();
        $taches = $tacheRepository->findAll();
        
        // Préparer les événements pour le calendrier
        $events = [];
        
        foreach ($plannings as $planning) {
            $employeName = $this->getEmployeName($planning->getEmployeId());
            $events[] = [
                'title' => '📅 ' . $employeName . ' - ' . $planning->getTypeShift(),
                'start' => $planning->getDate()?->format('Y-m-d') ?? '',
                'end' => $planning->getDate()?->format('Y-m-d') ?? '',
                'color' => $this->getShiftColor($planning->getTypeShift()),
                'type' => 'planning',
                'description' => 'Shift: ' . $planning->getTypeShift() . ' - Employé: ' . $employeName
            ];
        }
        
        foreach ($taches as $tache) {
            if ($tache->getDeadline()) {
                $events[] = [
                    'title' => '📋 ' . $tache->getTitre(),
                    'start' => $tache->getDeadline()->format('Y-m-d'),
                    'end' => $tache->getDeadline()->format('Y-m-d'),
                    'color' => $this->getPrioriteColor($tache->getPriorite()),
                    'type' => 'tache',
                    'description' => 'Priorité: ' . $tache->getPriorite() . ' - ' . ($tache->getDescription() ?? 'Aucune description')
                ];
            }
        }
        
        return $this->render('admin/calendar/index.html.twig', [
            'events' => json_encode($events),
        ]);
    }
    
    #[Route('/events', name: 'app_calendar_events', methods: ['GET'])]
    public function getEvents(PlanningRepository $planningRepository, TacheRepository $tacheRepository): JsonResponse
    {
        $events = [];
        
        // Récupérer les plannings
        $plannings = $planningRepository->findAll();
        foreach ($plannings as $planning) {
            $employeName = $this->getEmployeName($planning->getEmployeId());
            $events[] = [
                'id' => $planning->getId(),
                'title' => $employeName . ' (' . $planning->getTypeShift() . ')',
                'start' => $planning->getDate()?->format('Y-m-d') ?? '',
                'color' => $this->getShiftColor($planning->getTypeShift()),
                'textColor' => 'white',
                'description' => 'Shift: ' . $planning->getTypeShift(),
                'type' => 'planning'
            ];
        }
        
        // Récupérer les tâches avec deadline
        $taches = $tacheRepository->findAll();
        foreach ($taches as $tache) {
            if ($tache->getDeadline()) {
                $events[] = [
                    'id' => $tache->getId(),
                    'title' => $tache->getTitre(),
                    'start' => $tache->getDeadline()->format('Y-m-d'),
                    'color' => $this->getPrioriteColor($tache->getPriorite()),
                    'textColor' => 'white',
                    'description' => 'Priorité: ' . $tache->getPriorite(),
                    'type' => 'tache'
                ];
            }
        }
        
        return $this->json($events);
    }
    
    private function getEmployeName(?int $employeId): string
    {
        if (!$employeId) return 'Non assigné';
        
        $employe = $this->utilisateurRepository->find($employeId);
        
        if ($employe) {
            return $employe->getPrenom() . ' ' . $employe->getNom();
        }
        
        return 'Employé #' . $employeId;
    }
    
    private function getShiftColor(?string $shift): string
    {
        switch ($shift) {
            case 'JOUR': return '#3b82f6';  // Bleu
            case 'SOIR': return '#f59e0b';  // Orange
            case 'NUIT': return '#8b5cf6';  // Violet
            case 'CONGE': return '#fbbf24';  // Jaune
            case 'MALADIE': return '#ef4444'; // Rouge
            case 'FORMATION': return '#10b981'; // Vert
            default: return '#6b7280'; // Gris
        }
    }
    
    private function getPrioriteColor(?string $priorite): string
    {
        switch ($priorite) {
            case 'HAUTE': return '#ef4444';  // Rouge
            case 'MOYENNE': return '#f59e0b'; // Orange
            case 'BASSE': return '#10b981';  // Vert
            default: return '#6b7280'; // Gris
        }
    }
}