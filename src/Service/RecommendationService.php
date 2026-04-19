<?php

namespace App\Service;

use App\Repository\ParticipationRepository;
use App\Repository\EvenementRepository;
use App\Repository\EventFeedbackRepository;

class RecommendationService
{
    public function __construct(
        private ParticipationRepository  $participationRepo,
        private EvenementRepository      $evenementRepo,
        private EventFeedbackRepository  $feedbackRepo,
        private string                   $apiKey
    ) {}

    public function getRecommendations(string $userEmail): array
    {
        $participations = $this->participationRepo->findUserHistory($userEmail);

        if (empty($participations)) {
            return []; 
        }

        $attendedEventIds = [];
        $userHistory      = [];

        foreach ($participations as $p) {
            $event = $this->evenementRepo->find($p->getEventId());
            if (!$event) continue;

            $attendedEventIds[] = $event->getId();

            $feedback = $this->feedbackRepo->findOneBy([
                'evenement'  => $event,
                'user_email' => $userEmail,
            ]);

            $userHistory[] = [
                'titre'   => $event->getTitre(),
                'type'    => $event->getTypeEvent(),
                'lieu'    => $event->getLieu(),
                'rating'  => $feedback ? $feedback->getRating() : null,
            ];
        }

        $allEvents       = $this->evenementRepo->findVisibleForEmployees()
                               ->getQuery()
                               ->getResult();

        $availableEvents = array_filter(
            $allEvents,
            fn($e) => !in_array($e->getId(), $attendedEventIds)
                   && $e->getStatut() === 'planifier'
        );

        if (empty($availableEvents)) return [];

        $availableList = [];
        foreach ($availableEvents as $event) {
            $availableList[] = [
                'id'          => $event->getId(),
                'titre'       => $event->getTitre(),
                'type'        => $event->getTypeEvent(),
                'lieu'        => $event->getLieu(),
                'description' => mb_substr($event->getDescription() ?? '', 0, 100),
            ];
        }

        $prompt = "
            Tu es un système de recommandation d'événements pour une entreprise.
            
            Historique de l'employé (événements auxquels il a participé):
            " . json_encode($userHistory, JSON_UNESCAPED_UNICODE) . "
            
            Événements disponibles:
            " . json_encode(array_values($availableList), JSON_UNESCAPED_UNICODE) . "
            
            Analyse les préférences de l'employé et recommande les 6 événements 
            les plus pertinents pour lui.
            
            Réponds UNIQUEMENT avec un JSON valide dans ce format exact, sans texte avant ou après:
            [
                {
                    \"id\": 12,
                    \"reason\": \"Car vous avez apprécié la formation React, cette formation Vue.js pourrait vous intéresser\"
                },
                {
                    \"id\": 7,
                    \"reason\": \"Basé sur votre participation aux réunions d'équipe\"
                },
                {
                    \"id\": 3,
                    \"reason\": \"Ce séminaire correspond à vos centres d'intérêt\"
                }
            ]
        ";

        $response = $this->callGroq($prompt);
        if (!$response) return [];

   
        $clean = preg_replace('/```json|```/', '', $response);
        $clean = trim($clean);

        $recommended = json_decode($clean, true);
        if (!is_array($recommended)) return [];

        $result = [];
        foreach ($recommended as $rec) {
            foreach ($availableEvents as $event) {
                if ($event->getId() === $rec['id']) {
                    $result[] = [
                        'event'  => $event,
                        'reason' => $rec['reason'],
                    ];
                    break;
                }
            }
        }

        return $result;
    }

    private function callGroq(string $prompt): ?string
    {
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'      => 'llama-3.3-70b-versatile',
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 1000,
            ]),
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) return null;

        $data = json_decode($result, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }
}