<?php

namespace App\Service;

use App\Entity\Service;

class GroqService
{
    private array $services = [];
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = '';
    }

    public function setServices(array $services): void
    {
        $this->services = $services;
    }

    public function ask(string $question): string
    {
        $apiResponse = $this->callGroqAPI($question);
        if ($apiResponse) {
            return $apiResponse;
        }
        
        return $this->getLocalFallback($question);
    }

    private function callGroqAPI(string $question): ?string
    {
        $servicesArray = [];
        foreach ($this->services as $service) {
            $responsable = null;
            if ($service->getUtilisateur()) {
                try {
                    $responsable = $service->getUtilisateur()->getPrenom() . ' ' . $service->getUtilisateur()->getNom();
                } catch (\Exception $e) {
                    $responsable = null;
                }
            }
            
            $servicesArray[] = [
                'titre' => $service->getTitre(),
                'budget' => $service->getBudget(),
                'description' => $service->getDescription() ?: 'Aucune description',
                'categorie' => $service->getCategorie() ? $service->getCategorie()->getNom() : null,
                'responsable' => $responsable
            ];
        }
        
        $contexte = json_encode($servicesArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $prompt = "Tu es un assistant spécialisé dans la gestion de services. " .
                  "Voici les données actuelles des services au format JSON :\n\n" . $contexte .
                  "\n\nRéponds à cette question en français de façon naturelle et utile : " . $question;
        
        $data = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un assistant IA spécialisé dans l\'analyse de données de services. Tu réponds toujours en français de façon claire, précise et utile.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 800,
            'temperature' => 0.7
        ];
        
        try {
            $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    return $result['choices'][0]['message']['content'];
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getLocalFallback(string $question): string
    {
        $q = strtolower(trim($question));
        $services = $this->services;
        
        if (str_contains($q, 'budget total') || str_contains($q, 'total')) {
            $total = array_sum(array_map(fn($s) => $s->getBudget(), $services));
            return "💰 **Budget total** : " . number_format($total, 0, ',', ' ') . " DT";
        }
        
        if (str_contains($q, 'max') || str_contains($q, 'plus gros')) {
            $max = null;
            foreach ($services as $s) {
                if ($max === null || $s->getBudget() > $max->getBudget()) {
                    $max = $s;
                }
            }
            if ($max) {
                return "🏆 **Plus gros budget** : " . $max->getTitre() . " - " . number_format($max->getBudget(), 0, ',', ' ') . " DT";
            }
        }
        
        if (str_contains($q, 'liste') || str_contains($q, 'tous les services')) {
            $result = "📋 **Liste des " . count($services) . " services** :\n\n";
            foreach ($services as $index => $s) {
                $result .= ($index + 1) . ". **" . $s->getTitre() . "** - " . number_format($s->getBudget(), 0, ',', ' ') . " DT\n";
            }
            return $result;
        }
        
        if (str_contains($q, 'sans responsable')) {
            $without = array_filter($services, fn($s) => $s->getUtilisateur() === null);
            if (empty($without)) {
                return "Tous les services ont un responsable assigné.";
            }
            $result = "⚠️ " . count($without) . " services sans responsable** :\n\n";
            foreach ($without as $s) {
                $result .= "• " . $s->getTitre() . "\n";
            }
            return $result;
        }
        
        return "🤖 **Assistant IA**\n\nPosez-moi des questions comme :\n• 'Budget total'\n• 'Liste des services'\n• 'Plus gros budget'\n• 'Services sans responsable'";
    }
}