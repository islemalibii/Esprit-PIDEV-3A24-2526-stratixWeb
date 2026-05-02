<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GrokService 
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey) 
    {
        $this->client = $client;
        $this->apiKey = trim($apiKey);
    }

    /**
     * Suggère des projets basés sur l'inventaire
     */
    public function suggererProduits(array $items): array 
    {
        if (empty($items)) return [];
        $inventaire = $this->formaterInventaire($items);

        $messages = [
            ['role' => 'system', 'content' => "Tu es un expert IoT. Réponds UNIQUEMENT en JSON brut."],
            ['role' => 'user', 'content' => "Propose 3 idées de projets avec ce stock :\n$inventaire\nFormat : [{\"titre\":\"...\",\"description\":\"...\",\"composants\":\"...\"}]"]
        ];

        $jsonRaw = $this->callApi($messages);
        $jsonRaw = preg_replace('/^```json\s*|```$/m', '', $jsonRaw);

        return json_decode($jsonRaw, true) ?? [];
    }

    /**
     * Répond aux questions du Chatbot (Produits ou Ressources)
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
     * Cœur de l'appel API
     */
    private function callApi(array $messages): string
    {
        $isGroq = str_starts_with($this->apiKey, 'gsk_');
        
        $url = $isGroq 
            ? 'https://api.groq.com/openai/v1/chat/completions' 
            : 'https://api.x.ai/v1/chat/completions';

        // MISE À JOUR : On utilise grok-2 qui est plus stable en 2026
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
                    'temperature' => 0.4 // Réduit pour plus de précision sur les chiffres
                ],
                'verify_peer' => false, 
                'verify_host' => false,
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() === 200) {
                $result = $response->toArray();
                return $result['choices'][0]['message']['content'] ?? 'Pas de réponse.';
            }

            return "Erreur API : Code " . $response->getStatusCode() . " - " . $response->getContent(false);

        } catch (\Exception $e) {
            return "Erreur technique : " . $e->getMessage();
        }
    }

    /**
     * Formate l'inventaire en gérant dynamiquement les types d'entités
     */
    private function formaterInventaire(array $items): string
    {
        $texte = "";
        foreach ($items as $item) {
            $nom = method_exists($item, 'getNom') ? $item->getNom() : 'Inconnu';
            
            // On vérifie quel getter utiliser pour la quantité
            if (method_exists($item, 'getStockActuel')) {
                $quantite = $item->getStockActuel(); // Pour Produit
            } elseif (method_exists($item, 'getQuantite')) {
                $quantite = $item->getQuantite();    // Pour Ressource
            } else {
                $quantite = 0;
            }

            $texte .= sprintf("- %s (Quantité: %d)\n", $nom, $quantite);
        }
        return $texte;
    }
}