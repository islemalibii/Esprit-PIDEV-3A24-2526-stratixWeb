<?php

namespace App\Controller\Api;

use App\Entity\Tache;
use App\Entity\Planning;
use App\Repository\TacheRepository;
use App\Repository\PlanningRepository;
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
        $userId = $user?->getId();

        if (!$userId || !is_int($userId)) {
            return $this->json(['success' => false, 'events' => [], 'total' => 0]);
        }

        $startDate = $request->query->get('start');
        $endDate   = $request->query->get('end');
        $type      = $request->query->get('type', 'all');
        $priority  = $request->query->get('priority', 'all');

        $taches    = $tacheRepository->findBy(['employeId' => $userId]);
        $plannings = $planningRepository->findAll();

        $events = [];

        foreach ($taches as $tache) {
            $deadline = $tache->getDeadline();
            if (!$deadline) {
                continue;
            }

            $dateStr = $deadline->format('Y-m-d');
            if ($startDate && $dateStr < $startDate) continue;
            if ($endDate && $dateStr > $endDate) continue;
            if ($type !== 'all' && $type !== 'tache') continue;
            if ($priority !== 'all' && $tache->getPriorite() !== $priority) continue;

            $icon = match ($tache->getStatut()) {
                'A_FAIRE'   => '📌',
                'EN_COURS'  => '⚡',
                'TERMINEE'  => '✅',
                default     => '📋'
            };
            $color = match ($tache->getPriorite()) {
                'HAUTE'  => '#dc2626',
                'MOYENNE'=> '#f59e0b',
                default  => '#10b981'
            };

            $events[] = [
                'id'          => 'tache_' . $tache->getId(),
                'title'       => $icon . ' ' . ($tache->getTitre() ?? ''),
                'start'       => $dateStr,
                'end'         => $dateStr,
                'type'        => 'tache',
                'priority'    => $tache->getPriorite(),
                'color'       => $color,
                'status'      => $tache->getStatut(),
                'description' => $tache->getDescription() ?? '',
                'url'         => $this->generateUrl('app_tache_show', ['id' => $tache->getId()]),
            ];
        }

        foreach ($plannings as $planning) {
            $datePlanning = $planning->getDate();
            if (!$datePlanning) {
                continue;
            }

            $dateStr = $datePlanning->format('Y-m-d');
            if ($startDate && $dateStr < $startDate) continue;
            if ($endDate && $dateStr > $endDate) continue;
            if ($type !== 'all' && $type !== 'planning') continue;

            $titre = $planning->getTypeShift() ?? 'Planning';
            $events[] = [
                'id'          => 'planning_' . $planning->getId(),
                'title'       => '📅 ' . $titre,
                'start'       => $dateStr,
                'type'        => 'planning',
                'color'       => '#3b82f6',
                'description' => '',
            ];
        }

        $total      = count($taches);
        $terminees  = count(array_filter($taches, fn($t) => $t->getStatut() === 'TERMINEE'));
        $enCours    = count(array_filter($taches, fn($t) => $t->getStatut() === 'EN_COURS'));
        $aFaire     = count(array_filter($taches, fn($t) => $t->getStatut() === 'A_FAIRE'));

        return $this->json([
            'success' => true,
            'events'  => $events,
            'total'   => count($events),
            'stats'   => [
                'total_taches'    => $total,
                'terminees'       => $terminees,
                'en_cours'        => $enCours,
                'a_faire'         => $aFaire,
                'completion_rate' => $total > 0 ? round(($terminees / $total) * 100) : 0,
            ],
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

        if (str_starts_with($id, 'tache_')) {
            $tacheId = (int) str_replace('tache_', '', $id);
            $tache = $tacheRepository->find($tacheId);

            if (!$tache || $tache->getEmployeId() !== $user->getId()) {
                return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
            }

            if (isset($data['start']) && is_string($data['start'])) {
                $tache->setDeadline(new \DateTime($data['start']));
                $em->flush();
                return $this->json(['success' => true, 'message' => 'Date mise à jour']);
            }

            if (isset($data['status']) && is_string($data['status'])) {
                $tache->setStatut($data['status']);
                $em->flush();
                return $this->json(['success' => true, 'message' => 'Statut mis à jour']);
            }
        }

        return $this->json(['success' => false, 'error' => 'Type non supporté'], 400);
    }

    #[Route('/stats', name: 'api_calendar_stats', methods: ['GET'])]
    public function getStats(TacheRepository $tacheRepository): JsonResponse
    {
        $user = $this->getUser();
        $userId = $user?->getId();

        if (!$userId || !is_int($userId)) {
            return $this->json(['success' => true, 'stats' => [
                'total' => 0, 'terminees' => 0, 'en_cours' => 0, 'a_faire' => 0, 'completion_rate' => 0,
            ]]);
        }

        $taches = $tacheRepository->findBy(['employeId' => $userId]);

        $total = count($taches);
        $terminees = count(array_filter($taches, fn($t) => $t->getStatut() === 'TERMINEE'));
        $enCours   = count(array_filter($taches, fn($t) => $t->getStatut() === 'EN_COURS'));
        $aFaire    = count(array_filter($taches, fn($t) => $t->getStatut() === 'A_FAIRE'));
        $haute     = count(array_filter($taches, fn($t) => $t->getPriorite() === 'HAUTE'));
        $moyenne   = count(array_filter($taches, fn($t) => $t->getPriorite() === 'MOYENNE'));
        $basse     = count(array_filter($taches, fn($t) => $t->getPriorite() === 'BASSE'));

        return $this->json([
            'success' => true,
            'stats' => [
                'total' => $total,
                'terminees' => $terminees,
                'en_cours' => $enCours,
                'a_faire' => $aFaire,
                'completion_rate' => $total > 0 ? round(($terminees / $total) * 100) : 0,
                'priorites' => compact('haute', 'moyenne', 'basse'),
            ],
        ]);
    }
}