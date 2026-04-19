<?php

namespace App\Controller\Api;

use App\Service\EmailService;
use App\Repository\TacheRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/email')]
class EmailApiController extends AbstractController
{
    public function __construct(
        private EmailService $emailService,
        private TacheRepository $tacheRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {}

    // ==================== 1. TEST ====================
    #[Route('/test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return $this->json(['success' => true, 'message' => 'API Email prête']);
    }

    // ==================== 2. RAPPEL TÂCHE ====================
    #[Route('/rappel/tache/{id}', methods: ['POST', 'GET'])]
    public function sendTaskReminder(int $id): JsonResponse
    {
        $tache = $this->tacheRepository->find($id);
        if (!$tache) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $employe = $this->utilisateurRepository->find($tache->getEmployeId());
        if (!$employe || !$employe->getEmail()) {
            return $this->json(['error' => 'Employé sans email'], 400);
        }

        $result = $this->emailService->sendTaskReminder($id, $employe->getEmail());
        return $this->json(['success' => $result, 'to' => $employe->getEmail()]);
    }

    // ==================== 3. NOTIFICATION TÂCHE ====================
    #[Route('/notification/tache', methods: ['POST'])]
    public function sendTaskNotification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $to = $data['email'] ?? null;
        $title = $data['title'] ?? 'Nouvelle tâche';
        $description = $data['description'] ?? '';

        if (!$to) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $result = $this->emailService->sendTaskNotification($to, $title, $description);
        return $this->json(['success' => $result]);
    }

    // ==================== 4. NOTIFICATION PLANNING ====================
    #[Route('/notification/planning', methods: ['POST'])]
    public function sendPlanningNotification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $to = $data['email'] ?? null;
        $shiftType = $data['shift_type'] ?? 'JOUR';
        $date = $data['date'] ?? date('d/m/Y');
        $hours = $data['hours'] ?? '09:00 - 17:00';

        if (!$to) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $result = $this->emailService->sendPlanningNotification($to, $shiftType, $date, $hours);
        return $this->json(['success' => $result]);
    }

    // ==================== 5. BIENVENUE ====================
    #[Route('/bienvenue', methods: ['POST'])]
    public function sendWelcome(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $to = $data['email'] ?? null;
        $name = $data['name'] ?? 'Utilisateur';

        if (!$to) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $result = $this->emailService->sendWelcomeEmail($to, $name);
        return $this->json(['success' => $result]);
    }

    // ==================== 6. RÉINITIALISATION MOT DE PASSE ====================
    #[Route('/reset-password', methods: ['POST'])]
    public function sendResetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $to = $data['email'] ?? null;
        $code = $data['code'] ?? rand(100000, 999999);

        if (!$to) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $result = $this->emailService->sendPasswordResetEmail($to, (string)$code);
        return $this->json(['success' => $result, 'code' => $code]);
    }
}