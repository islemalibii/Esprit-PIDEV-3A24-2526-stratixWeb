<?php

namespace App\Controller\Api;

use App\Repository\TacheRepository;
use App\Repository\PlanningRepository;
use App\Repository\UtilisateurRepository;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chatbot')]
class ChatbotApiController extends AbstractController
{
    // TA CLÉ GROQ
    private const GROQ_API_KEY = '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';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';
    
    private $client;

    public function __construct(
        private TacheRepository $tacheRepository,
        private PlanningRepository $planningRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {
        $this->client = new Client([
            'verify' => false,
            'timeout' => 60,
        ]);
    }

    #[Route('/test', name: 'api_chatbot_test', methods: ['GET'])]
    public function testApi(): JsonResponse
    {
        try {
            $response = $this->client->post(self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . self::GROQ_API_KEY,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Dis "OK"']
                    ],
                    'max_tokens' => 10,
                ],
            ]);
            
            return $this->json(['status' => 'SUCCESS', 'message' => 'API Groq fonctionne !']);
        } catch (\Exception $e) {
            return $this->json(['status' => 'ERROR', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/message', name: 'api_chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');
        
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        
        // Vérifier les commandes métier d'abord
        $businessResponse = $this->processBusinessCommand($message, $userId);
        if ($businessResponse) {
            return $this->json(['success' => true, 'response' => $businessResponse]);
        }
        
        // TOUT le reste va à l'IA automatiquement
        $aiResponse = $this->sendToAI($message);
        
        return $this->json(['success' => true, 'response' => ['type' => 'text', 'content' => $aiResponse]]);
    }
    
    private function sendToAI(string $message): string
    {
        try {
            $response = $this->client->post(self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . self::GROQ_API_KEY,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un assistant IA amical pour STRATIX. Réponds de manière concise, utile et en français à toutes les questions."
                        ],
                        [
                            'role' => 'user',
                            'content' => $message
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ]
            ]);
            
            $responseBody = json_decode($response->getBody(), true);
            
            if (isset($responseBody['choices'][0]['message']['content'])) {
                return $responseBody['choices'][0]['message']['content'];
            }
            
            return "Je n'ai pas pu générer une réponse.";
            
        } catch (\Exception $e) {
            return "❌ Erreur: " . $e->getMessage();
        }
    }
    
    private function processBusinessCommand(string $message, ?int $userId): ?array
    {
        $msg = strtolower($message);
        
        if ($msg === 'aide' || $msg === 'help') {
            return ['type' => 'text', 'content' => "📋 **Commandes STRATIX :**\n\n• `Mes tâches` - Voir vos tâches\n• `Tâches en retard` - Tâches dépassées\n• `Prochains plannings` - Plannings à venir\n• `Statistiques` - Résumé\n• `Créer tâche: [titre]` - Création rapide\n\n💬 Pour tout le reste, je réponds automatiquement !"];
        }
        
        if (strpos($msg, 'mes taches') !== false || strpos($msg, 'mes tâches') !== false) {
            return $this->getMesTaches($userId);
        }
        
        if (strpos($msg, 'taches en retard') !== false || strpos($msg, 'tâches en retard') !== false) {
            return $this->getTachesEnRetard($userId);
        }
        
        if (strpos($msg, 'prochains plannings') !== false) {
            return $this->getProchainsPlannings($userId);
        }
        
        if (strpos($msg, 'statistiques') !== false) {
            return $this->getStatistics();
        }
        
        if (strpos($msg, 'creer tache:') !== false || strpos($msg, 'créer tâche:') !== false) {
            return $this->creerTacheRapide($message, $userId);
        }
        
        return null;
    }
    
    private function getMesTaches(?int $userId): array
    {
        if (!$userId) return ['type' => 'text', 'content' => "❌ Veuillez vous connecter."];
        
        $taches = $this->tacheRepository->findAll();
        $mesTaches = array_filter($taches, fn($t) => $t->getEmployeId() === $userId && $t->getStatut() !== 'TERMINEE');
        
        if (count($mesTaches) === 0) {
            return ['type' => 'text', 'content' => "✅ Aucune tâche en cours !"];
        }
        
        $content = "📋 **Vos tâches (" . count($mesTaches) . ")** :\n";
        foreach (array_slice($mesTaches, 0, 5) as $t) {
            $content .= "\n• **{$t->getTitre()}**";
        }
        return ['type' => 'text', 'content' => $content];
    }
    
    private function getTachesEnRetard(?int $userId): array
    {
        $today = new \DateTime();
        $taches = $this->tacheRepository->findAll();
        $retard = array_filter($taches, fn($t) => 
            $t->getDeadline() && $t->getDeadline() < $today && $t->getStatut() !== 'TERMINEE'
        );
        
        if (count($retard) === 0) {
            return ['type' => 'text', 'content' => "✅ Aucune tâche en retard !"];
        }
        
        $content = "⚠️ **Tâches en retard (" . count($retard) . ")** :\n";
        foreach (array_slice($retard, 0, 5) as $t) {
            $content .= "\n• **{$t->getTitre()}** - " . $t->getDeadline()->format('d/m/Y');
        }
        return ['type' => 'text', 'content' => $content];
    }
    
    private function getProchainsPlannings(?int $userId): array
    {
        $today = new \DateTime();
        $plannings = $this->planningRepository->findAll();
        $prochains = array_filter($plannings, fn($p) => $p->getDate() >= $today);
        usort($prochains, fn($a, $b) => $a->getDate() <=> $b->getDate());
        
        if (count($prochains) === 0) {
            return ['type' => 'text', 'content' => "📅 Aucun planning à venir."];
        }
        
        $content = "📅 **Prochains plannings** :\n";
        foreach (array_slice($prochains, 0, 5) as $p) {
            $content .= "\n• " . $p->getDate()->format('d/m/Y') . " - {$p->getTypeShift()}";
        }
        return ['type' => 'text', 'content' => $content];
    }
    
    private function getStatistics(): array
    {
        $taches = $this->tacheRepository->findAll();
        $total = count($taches);
        $aFaire = count(array_filter($taches, fn($t) => $t->getStatut() === 'A_FAIRE'));
        $enCours = count(array_filter($taches, fn($t) => $t->getStatut() === 'EN_COURS'));
        $terminees = count(array_filter($taches, fn($t) => $t->getStatut() === 'TERMINEE'));
        
        return ['type' => 'text', 'content' => "📊 **Résumé STRATIX** :\n\n📋 Total: $total\n⏳ En cours: $enCours\n✅ Terminées: $terminees\n📌 À faire: $aFaire"];
    }
    
    private function creerTacheRapide(string $message, ?int $userId): array
    {
        $titre = preg_replace('/^.*?(?:creer tache:|créer tâche:)\s*/i', '', $message);
        $titre = trim($titre);
        
        if (empty($titre)) {
            return ['type' => 'text', 'content' => "❌ Exemple: `Créer tâche: Faire le rapport`"];
        }
        
        $tache = new \App\Entity\Tache();
        $tache->setTitre($titre);
        $tache->setDescription("Créé via chatbot");
        $tache->setStatut('A_FAIRE');
        $tache->setPriorite('MOYENNE');
        $tache->setEmployeId($userId);
        $tache->setDeadline((new \DateTime())->modify('+7 days'));
        
        $this->tacheRepository->getEntityManager()->persist($tache);
        $this->tacheRepository->getEntityManager()->flush();
        
        return ['type' => 'text', 'content' => "✅ Tâche créée : **{$titre}**"];
    }
}