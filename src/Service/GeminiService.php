<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService {
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey) {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function suggererProduits(array $ressources): array {
        // Préparation du texte à envoyer
        $inventaire = "";
        foreach ($ressources as $r) {
            $inventaire .= "- " . $r->getNom() . " (Stock: " . $r->getQuantite() . ")\n";
        }

        $prompt = "Voici mon stock : \n$inventaire\n 
        Donne-moi 3 idées de produits à fabriquer avec ces composants. 
        Réponds uniquement en JSON avec ce format : 
        [{\"titre\": \"nom\", \"description\": \"quoi faire\", \"composants\": \"liste\"}]";

        $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $this->apiKey, [
            'json' => [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]
        ]);

        $result = $response->toArray();
        $jsonRaw = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // Nettoyage pour ne garder que le JSON
        $jsonClean = str_replace(['```json', '```'], '', $jsonRaw);
        return json_decode($jsonClean, true) ?? [];
    }
}