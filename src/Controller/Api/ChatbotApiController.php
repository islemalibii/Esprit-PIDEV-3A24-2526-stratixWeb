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
        $this->groqApiKey = $_ENV['GROQ_API_KEY'] ?? '';
        $this->client = new Client(['verify' => false, 'timeout' => 60]);
    }
 
    #[Route('/message', name: 'api_chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');
 
        if (empty($message)) {
            return $this->json(['success' => false, 'response' => ['type' => 'text', 'content' => '❌ Message vide.']]);
        }
 
        $user   = $this->getUser();
        $userId = $user ? $user->getId() : null;
 
        // Build real data context from DB
        $context = $this->buildContext($userId);
 
        // Send to AI with strict system prompt
        $response = $this->sendToGroq($message, $context);
 
        return $this->json(['success' => true, 'response' => ['type' => 'text', 'content' => $response]]);
    }
 
    private function buildContext(?int $userId): string
    {
        $today     = new \DateTime();
        $allTaches = $this->tacheRepository->findAll();
 
        // Global stats
        $total     = count($allTaches);
        $aFaire    = 0; $enCours = 0; $terminees = 0;
        $haute     = 0; $moyenne  = 0; $basse     = 0;
        $enRetard  = 0;
 
        foreach ($allTaches as $t) {
            if ($t->getStatut() === 'A_FAIRE')   $aFaire++;
            if ($t->getStatut() === 'EN_COURS')  $enCours++;
            if ($t->getStatut() === 'TERMINEE')  $terminees++;
            if ($t->getPriorite() === 'HAUTE')   $haute++;
            if ($t->getPriorite() === 'MOYENNE') $moyenne++;
            if ($t->getPriorite() === 'BASSE')   $basse++;
            if ($t->getDeadline() && $t->getDeadline() < $today && $t->getStatut() !== 'TERMINEE') {
                $enRetard++;
            }
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
 
        // All tasks detail
        $lines[] = "";
        $lines[] = "--- DÉTAIL DE TOUTES LES TÂCHES ---";
        foreach ($allTaches as $t) {
            $deadline  = $t->getDeadline() ? $t->getDeadline()->format('d/m/Y') : 'Aucune';
            $isLate    = $t->getDeadline() && $t->getDeadline() < $today && $t->getStatut() !== 'TERMINEE';
            $lateFlag  = $isLate ? ' [EN RETARD]' : '';
            $lines[]   = "• [{$t->getStatut()}] {$t->getTitre()} | Priorité: {$t->getPriorite()} | Deadline: {$deadline}{$lateFlag}";
            if ($t->getDescription()) {
                $lines[] = "  Description: " . mb_substr($t->getDescription(), 0, 80);
            }
        }
 
        // User's own tasks
        if ($userId) {
            $mesTaches = array_filter($allTaches, fn($t) => $t->getEmployeId() === $userId);
            $lines[]   = "";
            $lines[]   = "--- MES TÂCHES (utilisateur connecté) ---";
            if (empty($mesTaches)) {
                $lines[] = "Aucune tâche assignée à cet utilisateur.";
            } else {
                foreach ($mesTaches as $t) {
                    $deadline = $t->getDeadline() ? $t->getDeadline()->format('d/m/Y') : 'Aucune';
                    $isLate   = $t->getDeadline() && $t->getDeadline() < $today && $t->getStatut() !== 'TERMINEE';
                    $lines[]  = "• [{$t->getStatut()}] {$t->getTitre()} | Deadline: {$deadline}" . ($isLate ? ' [EN RETARD]' : '');
                }
            }
        }
 
        // Plannings
        try {
            $plannings  = $this->planningRepository->findAll();
            $prochains  = array_filter($plannings, fn($p) => $p->getDate() >= $today);
            $passes     = array_filter($plannings, fn($p) => $p->getDate() < $today);
            usort($prochains, fn($a, $b) => $a->getDate() <=> $b->getDate());
 
            $lines[] = "";
            $lines[] = "--- PLANNINGS ---";
            $lines[] = "Total plannings: " . count($plannings);
            $lines[] = "À venir: " . count($prochains);
            $lines[] = "Passés: " . count($passes);
 
            if (!empty($prochains)) {
                $lines[] = "Prochains plannings:";
                foreach (array_slice($prochains, 0, 5) as $p) {
                    $lines[] = "• " . $p->getDate()->format('d/m/Y') . " - " . $p->getTypeShift();
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
2. Si la question ne concerne pas STRATIX (météo, actualités, blagues, cuisine, etc.), réponds EXACTEMENT : "Je suis uniquement disponible pour répondre aux questions concernant l'application STRATIX (tâches, plannings, statistiques)."
3. Tu bases tes réponses UNIQUEMENT sur les données ci-dessous. Ne jamais inventer.
4. Tes réponses sont en français, précises, directes et utiles.
5. Pour les statistiques, donne des chiffres exacts depuis les données.
6. Pour "résume les stats" ou "statistiques", fournis un résumé complet avec tous les chiffres.
 
DONNÉES ACTUELLES DE STRATIX :
$context
PROMPT;
 
        try {
            $response = $this->client->post(self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'    => self::MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $message],
                    ],
                    'temperature' => 0.1,
                    'max_tokens'  => 600,
                ],
            ]);
 
            $body = json_decode($response->getBody(), true);
            return $body['choices'][0]['message']['content'] ?? "Je n'ai pas pu générer une réponse.";
 
        } catch (\Exception $e) {
            return "❌ Erreur de connexion à l'IA : " . $e->getMessage();
        }
    }
}