<?php

namespace App\Controller\Api;

use App\Repository\TacheRepository;
use App\Repository\PlanningRepository;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chatbot')]
class ChatbotApiController extends AbstractController
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';

    private Client $client;
    private string $groqApiKey;

    public function __construct(
        private TacheRepository $tacheRepository,
        private PlanningRepository $planningRepository,
    ) {
        $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        $this->groqApiKey = is_string($apiKey) ? $apiKey : '';
        $this->client = new Client(['verify' => false, 'timeout' => 60]);
    }

    #[Route('/message', name: 'api_chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = '';
        if (is_array($data) && isset($data['message']) && is_string($data['message'])) {
            $message = trim($data['message']);
        }

        if ($message === '') {
            return $this->json(['success' => false, 'response' => ['type' => 'text', 'content' => '❌ Message vide.']]);
        }

        $user = $this->getUser();
        $userId = $user?->getId();
        $context = $this->buildContext(is_int($userId) ? $userId : null);
        $response = $this->sendToGroq($message, $context);

        return $this->json(['success' => true, 'response' => ['type' => 'text', 'content' => $response]]);
    }

    private function buildContext(?int $userId): string
    {
        $today = new \DateTime();
        $allTaches = $this->tacheRepository->findAll();

        $total = count($allTaches);
        $aFaire = $enCours = $terminees = $haute = $moyenne = $basse = $enRetard = 0;

        foreach ($allTaches as $t) {
            $statut = $t->getStatut();
            $priorite = $t->getPriorite();
            $deadline = $t->getDeadline();

            if ($statut === 'A_FAIRE') $aFaire++;
            if ($statut === 'EN_COURS') $enCours++;
            if ($statut === 'TERMINEE') $terminees++;
            if ($priorite === 'HAUTE') $haute++;
            if ($priorite === 'MOYENNE') $moyenne++;
            if ($priorite === 'BASSE') $basse++;
            if ($deadline && $deadline < $today && $statut !== 'TERMINEE') $enRetard++;
        }

        $tauxCompletion = $total > 0 ? round(($terminees / $total) * 100, 1) : 0;

        $lines = [];
        $lines[] = "=== DONNÉES STRATIX EN TEMPS RÉEL ===";
        $lines[] = "Date aujourd'hui: " . $today->format('d/m/Y');
        $lines[] = "";
        $lines[] = "--- STATISTIQUES GLOBALES DES TÂCHES ---";
        $lines[] = "Total tâches: $total";
        $lines[] = "À faire: $aFaire";
        $lines[] = "En cours: $enCours";
        $lines[] = "Terminées: $terminees";
        $lines[] = "En retard: $enRetard";
        $lines[] = "Taux de complétion: $tauxCompletion%";
        $lines[] = "Priorité haute: $haute | Moyenne: $moyenne | Basse: $basse";

        $lines[] = "";
        $lines[] = "--- DÉTAIL DE TOUTES LES TÂCHES ---";
        foreach ($allTaches as $t) {
            $deadline = $t->getDeadline();
            $deadlineStr = $deadline ? $deadline->format('d/m/Y') : 'Aucune';
            $isLate = $deadline && $deadline < $today && $t->getStatut() !== 'TERMINEE';
            $lateFlag = $isLate ? ' [EN RETARD]' : '';
            $lines[] = "• [{$t->getStatut()}] {$t->getTitre()} | Priorité: {$t->getPriorite()} | Deadline: {$deadlineStr}{$lateFlag}";
            $desc = $t->getDescription();
            if ($desc) {
                $lines[] = "  Description: " . mb_substr($desc, 0, 80);
            }
        }

        if ($userId) {
            $mesTaches = array_filter($allTaches, fn($t) => $t->getEmployeId() === $userId);
            $lines[] = "";
            $lines[] = "--- MES TÂCHES (utilisateur connecté) ---";
            if (empty($mesTaches)) {
                $lines[] = "Aucune tâche assignée à cet utilisateur.";
            } else {
                foreach ($mesTaches as $t) {
                    $deadline = $t->getDeadline();
                    $deadlineStr = $deadline ? $deadline->format('d/m/Y') : 'Aucune';
                    $isLate = $deadline && $deadline < $today && $t->getStatut() !== 'TERMINEE';
                    $lines[] = "• [{$t->getStatut()}] {$t->getTitre()} | Deadline: {$deadlineStr}" . ($isLate ? ' [EN RETARD]' : '');
                }
            }
        }

        try {
            $plannings = $this->planningRepository->findAll();
            $prochains = array_filter($plannings, fn($p) => $p->getDate() >= $today);
            $passes = array_filter($plannings, fn($p) => $p->getDate() < $today);
            usort($prochains, fn($a, $b) => $a->getDate() <=> $b->getDate());

            $lines[] = "";
            $lines[] = "--- PLANNINGS ---";
            $lines[] = "Total plannings: " . count($plannings);
            $lines[] = "À venir: " . count($prochains);
            $lines[] = "Passés: " . count($passes);

            if (!empty($prochains)) {
                $lines[] = "Prochains plannings:";
                foreach (array_slice($prochains, 0, 5) as $p) {
                    $date = $p->getDate();
                    $dateStr = $date ? $date->format('d/m/Y') : 'Date inconnue';
                    $lines[] = "• " . $dateStr . " - " . ($p->getTypeShift() ?? '');
                }
            }
        } catch (\Exception $e) {
            $lines[] = "Plannings: données non disponibles.";
        }

        return implode("\n", $lines);
    }

    private function sendToGroq(string $message, string $context): string
    {
        $systemPrompt = <<<PROMPT
Tu es l'assistant intelligent de l'application STRATIX, un système de gestion des tâches et plannings.

RÈGLES ABSOLUES :
1. Tu réponds UNIQUEMENT aux questions concernant STRATIX : tâches, plannings, statistiques, performances.
2. Si la question ne concerne pas STRATIX, réponds EXACTEMENT : "Je suis uniquement disponible pour répondre aux questions concernant l'application STRATIX (tâches, plannings, statistiques)."
3. Tu bases tes réponses UNIQUEMENT sur les données ci-dessous.
4. Tes réponses sont en français, précises, directes et utiles.
5. Pour les statistiques, donne des chiffres exacts.

DONNÉES ACTUELLES DE STRATIX :
$context
PROMPT;

        if ($this->groqApiKey === '') {
            return "⚠️ Clé API Groq non configurée.";
        }

        try {
            $response = $this->client->post(self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 600,
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            // Vérification stricte de la structure de la réponse
            if (
                is_array($body) &&
                isset($body['choices']) &&
                is_array($body['choices']) &&
                isset($body['choices'][0]) &&
                is_array($body['choices'][0]) &&
                isset($body['choices'][0]['message']) &&
                is_array($body['choices'][0]['message']) &&
                isset($body['choices'][0]['message']['content']) &&
                is_string($body['choices'][0]['message']['content'])
            ) {
                return $body['choices'][0]['message']['content'];
            }

            return "Je n'ai pas pu générer une réponse (format inattendu).";
        } catch (\Exception $e) {
            return "❌ Erreur de connexion à l'IA : " . $e->getMessage();
        }
    }
}