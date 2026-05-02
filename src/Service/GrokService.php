<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GrokService 
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey) 
    {
        $this->client = $client;
        $this->apiKey = trim($apiKey);
    }

    /**
     * @param array<mixed> $items
     * @return array<mixed>
     */
    public function suggererProduits(array $items): array 
    {
        if (empty($items)) return [];
        $inventaire = $this->formaterInventaire($items);

        $messages = [
            ['role' => 'system', 'content' => "Tu es un expert IoT. Réponds UNIQUEMENT en JSON brut."],
            ['role' => 'user', 'content' => "Propose 3 idées de projets avec ce stock :\n$inventaire\nFormat : [{\"titre\":\"...\",\"description\":\"...\",\"composants\":\"...\"}]"]        ];

        $jsonRaw = $this->callApi($messages);
        $jsonRaw = preg_replace('/^```json\s*|```$/m', '', $jsonRaw) ?? '';

        /** @var array<mixed>|null $decoded */
        $decoded = json_decode($jsonRaw, true);
        
        // Correction 1 : PHPStan sait maintenant que $decoded peut être null
        return $decoded ?? [];
    }

    /**
     * @param string $question
     * @param array<mixed> $items
     */
    public function repondreQuestionStock(string $question, array $items): string 
    {
        $inventaire = $this->formaterInventaire($items);

        $messages = [
            ['role' => 'system', 'content' => "Tu es l'assistant intelligent Stratix. Réponds de façon concise en français."],
            ['role' => 'user', 'content' => "Voici mon inventaire actuel :\n$inventaire\nQuestion : $question"]
        ];

        return $this->callApi($messages);
    }

    /**
     * @param array<array{role: string, content: string}> $messages
     */
    private function callApi(array $messages): string
    {
        $isGroq = str_starts_with($this->apiKey, 'gsk_');
        $url = $isGroq ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://api.x.ai/v1/chat/completions';
        $model = $isGroq ? 'llama-3.3-70b-versatile' : 'grok-2';

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.4
                ],
                'verify_peer' => false, 
                'verify_host' => false,
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() === 200) {
                /** @var array{choices: array<array{message: array{content: string}}>} $result */
                $result = $response->toArray();
                return $result['choices'][0]['message']['content'] ?? 'Pas de réponse.';
            }

            return "Erreur API : Code " . $response->getStatusCode();
        } catch (\Exception $e) {
            return "Erreur technique : " . $e->getMessage();
        }
    }

    /**
     * @param array<mixed> $items
     */
    private function formaterInventaire(array $items): string
    {
        $texte = "";
        foreach ($items as $item) {
            // Correction 2 : On ne vérifie plus is_object car PHPStan est déjà convaincu
            // mais on utilise method_exists par sécurité
            $nom = (is_object($item) && method_exists($item, 'getNom')) ? $item->getNom() : 'Inconnu';
            
            $quantite = 0;
            if (is_object($item)) {
                if (method_exists($item, 'getStockActuel')) {
                    $quantite = $item->getStockActuel();
                } elseif (method_exists($item, 'getQuantite')) {
                    $quantite = $item->getQuantite();
                }
            }

            $texte .= sprintf("- %s (Quantité: %d)\n", (string)$nom, (int)$quantite);
        }
        return $texte;
    }
}