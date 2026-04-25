<?php

namespace App\Controller\Api;

use App\Service\SmsService;
use App\Repository\TacheRepository;
use App\Repository\PlanningRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sms')]
class SmsApiController extends AbstractController
{
    public function __construct(
        private SmsService $smsService,
        private TacheRepository $tacheRepository,
        private PlanningRepository $planningRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {}

    // Envoyer un SMS à un employé
    #[Route('/send', methods: ['POST'])]
    public function sendSms(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $employeId = $data['employe_id'] ?? null;
        $message = $data['message'] ?? null;

        if (!$employeId || !$message) {
            return $this->json(['error' => 'employe_id et message requis'], 400);
        }

        $employe = $this->utilisateurRepository->find($employeId);
        if (!$employe || !$employe->getTel()) {
            return $this->json(['error' => 'Employé sans numéro de téléphone'], 400);
        }

        $result = $this->smsService->sendSms($employe->getTel(), $message);
        return $this->json($result);
    }

    // Rappel tâche à J-1
    #[Route('/rappel/tache/{id}', methods: ['POST'])]
    public function sendTaskReminder(int $id): JsonResponse
    {
        $tache = $this->tacheRepository->find($id);
        if (!$tache) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $employe = $this->utilisateurRepository->find($tache->getEmployeId());
        if (!$employe || !$employe->getTel()) {
            return $this->json(['error' => 'Employé sans numéro'], 400);
        }

        $result = $this->smsService->sendTaskReminder($id, $employe->getTel());
        return $this->json($result);
    }

    // Alerte tâche en retard
    #[Route('/alerte/tache/{id}', methods: ['POST'])]
    public function sendLateAlert(int $id): JsonResponse
    {
        $tache = $this->tacheRepository->find($id);
        if (!$tache) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $employe = $this->utilisateurRepository->find($tache->getEmployeId());
        if (!$employe || !$employe->getTel()) {
            return $this->json(['error' => 'Employé sans numéro'], 400);
        }

        $result = $this->smsService->sendLateTaskAlert($id, $employe->getTel());
        return $this->json($result);
    }

    // Envoyer des rappels à tous les employés
    #[Route('/rappel/masse', methods: ['POST'])]
    public function sendMassReminder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? 'Rappel STRATIX : Pensez à vérifier vos tâches et plannings !';
        
        $employes = $this->utilisateurRepository->findAll();
        $results = [];
        $sent = 0;

        foreach ($employes as $e) {
            if ($e->getTel()) {
                $result = $this->smsService->sendSms($e->getTel(), $message);
                if ($result['success']) $sent++;
                $results[] = ['employe' => $e->getEmail(), 'result' => $result];
            }
        }

        return $this->json(['success' => true, 'sent' => $sent, 'total' => count($employes), 'details' => $results]);
    }
}