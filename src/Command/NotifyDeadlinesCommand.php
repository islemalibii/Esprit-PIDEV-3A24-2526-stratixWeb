<?php

namespace App\Command;

use App\Repository\ProjetRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Transport\Transports;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:notify-deadlines',
    description: 'Envoie des rappels générés par l\'IA Llama 3 aux membres de STRATIX'
)]
class NotifyDeadlinesCommand extends Command
{
    private string $groqApiKey;

    public function __construct(
        private ProjetRepository $projetRepository,
        private Transports $projetMailer,
        private HttpClientInterface $httpClient,
        // Ta clé API actuelle
        string $groqApiKey = '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'
    ) {
        $this->groqApiKey = $groqApiKey;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Transport: ' . get_class($this->projetMailer));
        $projets = $this->projetRepository->findProjetsProchesEcheance(7);

        foreach ($projets as $projet) {
            $nomProjet = $projet->getNom();
            $output->writeln("<info>--- Projet : " . $nomProjet . " ---</info>");

            // Récupération de tous les acteurs du projet
            $destinataires = array_merge(
                [$projet->getResponsable()],
                $projet->getMembres()->toArray()
            );

            foreach ($destinataires as $user) {
                if ($user && $user->getEmail()) {
                    // APPEL À LA VRAIE IA
                    $aiMessage = $this->generateAiMessage($nomProjet, $user->getNom());
                    
                    try {
                        $this->sendEmail($user->getEmail(), $nomProjet, $aiMessage, $user->getNom());
                        $output->writeln("Email IA envoyé à : " . $user->getNom());
                    } catch (\Exception $e) {
                        $output->writeln("<error>Erreur Mail : " . $e->getMessage() . "</error>");
                    }
                    
                    // Pause de 1s pour laisser respirer Mailpit et l'API
                    sleep(1);
                }
            }
        }

        return Command::SUCCESS;
    }

private function generateAiMessage(string $projectName, ?string $userName): string
{
    try {
        $name = $userName ?: 'l\'équipe';

        $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'verify_peer' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . trim($this->groqApiKey),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                // ON CHANGE LE MODÈLE ICI
                'model' => 'llama-3.3-70b-versatile', 
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => "En tant que coach STRATIX, donne une seule phrase motivante et originale pour $name sur le projet $projectName. Sois bref."
                    ]
                ],
                'temperature' => 0.8,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            $errorData = $response->toArray(false);
            dump("DÉTAIL ERREUR : ", $errorData);
            throw new \Exception("Erreur API");
        }

        $data = $response->toArray();
        return $data['choices'][0]['message']['content'];

    } catch (\Exception $e) {
        // Message de secours si l'API est surchargée
        return "C'est le moment de briller sur $projectName, $userName ! L'équipe Stratix croit en vous.";
    }
}

    private function sendEmail(string $to, string $projectName, string $aiMessage, ?string $userName): void
    {
        $email = (new Email())
            ->from('noreply@stratix.com')
            ->to($to)
            ->subject("🚀 STRATIX IA : Motivation pour $projectName")
            ->html("
                <div style='font-family: Arial, sans-serif; border: 1px solid #eee; padding: 20px; border-radius: 10px; max-width: 600px;'>
                    <h2 style='color: #2c3e50;'>Bonjour " . ($userName ?? 'à l\'équipe') . ",</h2>
                    <p>Votre projet <strong>$projectName</strong> arrive à échéance bientôt.</p>
                    <div style='background-color: #f8f9fa; padding: 20px; border-left: 4px solid #3498db; font-size: 1.1em; color: #34495e;'>
                        \"$aiMessage\"
                    </div>
                    <p style='color: #7f8c8d; font-size: 0.9em;'>— Généré par l'intelligence artificielle de STRATIX</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p><a href='#' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Accéder à Stratix</a></p>
                </div>
            ");

            $email->getHeaders()->addTextHeader('X-Transport', 'projet');
    
            $this->projetMailer->send($email, null);        }
}