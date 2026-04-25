<?php

namespace App\Service;

class MeetingSummaryService
{
    public function __construct(private string $apiKey) {}  // 👈 $apiKey

    public function generateSummary(array $feedbacks, string $eventTitle): ?string
    {
        if (empty($feedbacks)) return null;

        $feedbackList = '';
        foreach ($feedbacks as $feedback) {
            $stars       = str_repeat('★', $feedback->getRating()) . str_repeat('☆', 5 - $feedback->getRating());
            $commentaire = $feedback->getCommentaire() ?? 'Aucun commentaire';
            $feedbackList .= "- Note: {$stars} ({$feedback->getRating()}/5) | Commentaire: {$commentaire}\n";
        }

        $totalRatings = array_sum(array_map(fn($f) => $f->getRating(), $feedbacks));
        $average      = round($totalRatings / count($feedbacks), 1);

        $prompt = "
            Tu es un assistant RH professionnel francophone.
            
            Voici les avis des employés sur l'événement \"$eventTitle\":
            
            $feedbackList
            
            Note moyenne: $average/5 sur " . count($feedbacks) . " avis.
            
            Génère un rapport de synthèse professionnel en français avec:
            1. Les points positifs mentionnés
            2. Les points à améliorer
            3. Une conclusion avec recommandations
            
            Le rapport doit être concis (max 200 mots), professionnel et constructif.
        ";

        return $this->callGemini($prompt);
    }

    private function callGemini(string $prompt): ?string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=' . $this->apiKey;        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false, 
            CURLOPT_POSTFIELDS     => json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]),
        ]);

        $result = curl_exec($ch);

        if (!$result) {
            dump(curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $data = json_decode($result, true);
        dump($data);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
}