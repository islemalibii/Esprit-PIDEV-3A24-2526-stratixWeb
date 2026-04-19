<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiResumeService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = trim($apiKey);
    }

    public function generateSummary(string $text): string
    {
        // 1. Nettoyage de sécurité : On garde uniquement les caractères alphanumériques et la ponctuation de base
        // Cela évite les erreurs 400 dues à des caractères invisibles ou binaires issus du PDF
        $cleanText = preg_replace('/[^\p{L}\p{N}\s\.\,\?\!\'\-]/u', '', $text);
        $cleanText = mb_substr($cleanText, 0, 3000); // On limite la taille

        try {
            $response = $this->client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    // On utilise le modèle Llama 3.3 qui est le plus stable actuellement
                    'model' => 'llama-3.3-70b-versatile', 
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert en gestion de projet. Réponds uniquement par un objet JSON valide avec les clés "resume_court" et "resume_detaille". Ne rédige aucune phrase avant ou après le JSON.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Analyse ce texte et génère le JSON en français : " . $cleanText
                        ]
                    ],
                    'temperature' => 0.3,
                ],
            ]);

            // Récupération de la réponse
            $content = $response->getContent(false);
            $data = json_decode($content, true);

            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }

            // Si Groq renvoie une erreur spécifique dans le JSON
            if (isset($data['error']['message'])) {
                return json_encode([
                    'resume_court' => 'Erreur API Groq',
                    'resume_detaille' => $data['error']['message']
                ]);
            }

            throw new \Exception('Format de réponse inconnu');

        } catch (\Exception $e) {
            return json_encode([
                'resume_court' => 'Erreur de connexion',
                'resume_detaille' => 'Détails : ' . $e->getMessage()
            ]);
        }
    }
}