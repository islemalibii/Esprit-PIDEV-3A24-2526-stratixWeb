<?php

namespace App\Service;

use Twilio\Rest\Client;
use App\Repository\TacheRepository;
use App\Repository\UtilisateurRepository;

class SmsService
{
    private $client;
    private $twilioPhone;

    public function __construct(
        private TacheRepository $tacheRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {
        $sid = $_ENV['TWILIO_SID'] ?? '';
        $token = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
        $this->twilioPhone = $_ENV['TWILIO_PHONE_NUMBER'] ?? '';
        
        if ($sid && $token) {
            $this->client = new Client($sid, $token);
        }
    }

    // Envoyer un SMS
    public function sendSms(string $to, string $message): array
    {
        if (!$this->client) {
            return ['success' => false, 'message' => 'Twilio non configuré'];
        }

        try {
            $this->client->messages->create($to, [
                'from' => $this->twilioPhone,
                'body' => $message
            ]);
            return ['success' => true, 'message' => 'SMS envoyé'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Rappel de tâche (J-1)
    public function sendTaskReminder(int $tacheId, string $phoneNumber): array
    {
        $tache = $this->tacheRepository->find($tacheId);
        if (!$tache) {
            return ['success' => false, 'message' => 'Tâche non trouvée'];
        }

        $message = sprintf(
            "📋 STRATIX - Rappel :\n\nTâche : %s\nDeadline : %s\nPriorité : %s\n\nNe l'oubliez pas !",
            $tache->getTitre(),
            $tache->getDeadline()?->format('d/m/Y'),
            $tache->getPriorite()
        );

        return $this->sendSms($phoneNumber, $message);
    }

    // Alerte tâche en retard
    public function sendLateTaskAlert(int $tacheId, string $phoneNumber): array
    {
        $tache = $this->tacheRepository->find($tacheId);
        if (!$tache) {
            return ['success' => false, 'message' => 'Tâche non trouvée'];
        }

        $message = sprintf(
            "⚠️ STRATIX - ALERTE RETARD !\n\nTâche : %s\nDeadline : %s (dépassée)\nPriorité : %s\n\nÀ traiter immédiatement !",
            $tache->getTitre(),
            $tache->getDeadline()?->format('d/m/Y'),
            $tache->getPriorite()
        );

        return $this->sendSms($phoneNumber, $message);
    }

    // Rappel planning du jour
    public function sendPlanningReminder(int $planningId, string $phoneNumber): array
    {
        $planning = $this->planningRepository->find($planningId);
        if (!$planning) {
            return ['success' => false, 'message' => 'Planning non trouvé'];
        }

        $shiftLabels = [
            'JOUR' => '☀️ Jour',
            'SOIR' => '🌆 Soir',
            'NUIT' => '🌙 Nuit'
        ];
        $shift = $shiftLabels[$planning->getTypeShift()] ?? $planning->getTypeShift();

        $message = sprintf(
            "📅 STRATIX - Planning du jour\n\nDate : %s\nType : %s\nHoraires : %s - %s\n\nBonne journée !",
            $planning->getDate()->format('d/m/Y'),
            $shift,
            $planning->getHeureDebut()?->format('H:i') ?? '---',
            $planning->getHeureFin()?->format('H:i') ?? '---'
        );

        return $this->sendSms($phoneNumber, $message);
    }
}