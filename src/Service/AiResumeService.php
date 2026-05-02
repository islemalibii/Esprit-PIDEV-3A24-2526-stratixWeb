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
        // 1. Nettoyage de sécurité
        $replacedText = preg_replace('/[^\p{L}\p{N}\s\.\,\?\!\'\-]/u', '', $text);
        
        // Correction ligne 23 : On s'assure que le résultat est une string avant mb_substr
        $cleanText = is_string($replacedText) ? $replacedText : '';
        $cleanText = mb_substr($cleanText, 0, 3000);

        try {
            $response = $this->client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
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

            $content = $response->getContent(false);
            $data = json_decode($content, true);

            // On vérifie que $data est bien un tableau après le décodage
            if (is_array($data) && isset($data['choices'][0]['message']['content'])) {
                return (string)$data['choices'][0]['message']['content'];
            }

            // Si Groq renvoie une erreur spécifique dans le JSON
            if (is_array($data) && isset($data['error']['message'])) {
                $errorResult = json_encode([
                    'resume_court' => 'Erreur API Groq',
                    'resume_detaille' => (string)$data['error']['message']
                ]);
                return is_string($errorResult) ? $errorResult : '{"error": "JSON encoding failed"}';
            }

            throw new \Exception('Format de réponse inconnu');

        } catch (\Exception $e) {
            // Correction lignes 58 et 67 : On sécurise le retour de json_encode
            $fallback = json_encode([
                'resume_court' => 'Erreur de connexion',
                'resume_detaille' => 'Détails : ' . $e->getMessage()
            ]);
            
            return is_string($fallback) ? $fallback : '{"error": "Critical service failure"}';
        }
    }
}