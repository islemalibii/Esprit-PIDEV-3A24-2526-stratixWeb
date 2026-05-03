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
    public function __construct(
        private UtilisateurRepository $utilisateurRepository
    ) {}

    #[Route('/', name: 'app_calendar_index')]
    public function index(PlanningRepository $planningRepository, TacheRepository $tacheRepository): Response
    {
        $plannings = $planningRepository->findAll();
        $taches = $tacheRepository->findAll();
        $events = [];

        foreach ($plannings as $planning) {
            $date = $planning->getDate();
            if (!$date) {
                continue;
            }

            $employeName = $this->getEmployeName($planning->getEmployeId());
            $events[] = [
                'title' => '📅 ' . $employeName . ' - ' . ($planning->getTypeShift() ?? ''),
                'start' => $date->format('Y-m-d'),
                'end' => $date->format('Y-m-d'),
                'color' => $this->getShiftColor($planning->getTypeShift()),
                'type' => 'planning',
                'description' => 'Shift: ' . ($planning->getTypeShift() ?? '') . ' - Employé: ' . $employeName,
            ];
        }

        foreach ($taches as $tache) {
            $deadline = $tache->getDeadline();
            if (!$deadline) {
                continue;
            }

            $events[] = [
                'title' => '📋 ' . ($tache->getTitre() ?? ''),
                'start' => $deadline->format('Y-m-d'),
                'end' => $deadline->format('Y-m-d'),
                'color' => $this->getPrioriteColor($tache->getPriorite()),
                'type' => 'tache',
                'description' => 'Priorité: ' . ($tache->getPriorite() ?? '') . ' - ' . ($tache->getDescription() ?? 'Aucune description'),
            ];
        }

        return $this->render('admin/calendar/index.html.twig', [
            'events' => json_encode($events),
        ]);
    }

    #[Route('/events', name: 'app_calendar_events', methods: ['GET'])]
    public function getEvents(PlanningRepository $planningRepository, TacheRepository $tacheRepository): JsonResponse
    {
        $events = [];

        foreach ($planningRepository->findAll() as $planning) {
            $date = $planning->getDate();
            if (!$date) {
                continue;
            }

            $events[] = [
                'id' => $planning->getId(),
                'title' => $this->getEmployeName($planning->getEmployeId()) . ' (' . ($planning->getTypeShift() ?? '') . ')',
                'start' => $date->format('Y-m-d'),
                'color' => $this->getShiftColor($planning->getTypeShift()),
                'textColor' => 'white',
                'description' => 'Shift: ' . ($planning->getTypeShift() ?? ''),
                'type' => 'planning',
            ];
        }

        foreach ($tacheRepository->findAll() as $tache) {
            $deadline = $tache->getDeadline();
            if (!$deadline) {
                continue;
            }

            $events[] = [
                'id' => $tache->getId(),
                'title' => $tache->getTitre() ?? '',
                'start' => $deadline->format('Y-m-d'),
                'color' => $this->getPrioriteColor($tache->getPriorite()),
                'textColor' => 'white',
                'description' => 'Priorité: ' . ($tache->getPriorite() ?? ''),
                'type' => 'tache',
            ];
        }

        return $this->json($events);
    }

    private function getEmployeName(?int $employeId): string
    {
        if (!$employeId) {
            return 'Non assigné';
        }
        $employe = $this->utilisateurRepository->find($employeId);
        return $employe ? ($employe->getPrenom() . ' ' . $employe->getNom()) : 'Employé #' . $employeId;
    }

    private function getShiftColor(?string $shift): string
    {
        return match ($shift) {
            'JOUR' => '#3b82f6',
            'SOIR' => '#f59e0b',
            'NUIT' => '#8b5cf6',
            'CONGE' => '#fbbf24',
            'MALADIE' => '#ef4444',
            'FORMATION' => '#10b981',
            default => '#6b7280',
        };
    }

    private function getPrioriteColor(?string $priorite): string
    {
        return match ($priorite) {
            'HAUTE' => '#ef4444',
            'MOYENNE' => '#f59e0b',
            'BASSE' => '#10b981',
            default => '#6b7280',
        };
    }
}