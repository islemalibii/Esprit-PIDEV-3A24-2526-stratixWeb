<?php

namespace App\Service;

use App\Repository\TacheRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    private $mailer;
    private $tacheRepository;
    private $utilisateurRepository;

    // Configuration SMTP (comme ton Java)
    private const SMTP_HOST = 'smtp.gmail.com';
    private const SMTP_PORT = '587';
    private const EMAIL_FROM = 'tahahamdouni11@gmail.com';
    private const EMAIL_PASSWORD = 'nvrg otuh bpji nlnr';

    public function __construct(
        MailerInterface $mailer,
        TacheRepository $tacheRepository,
        UtilisateurRepository $utilisateurRepository
    ) {
        $this->mailer = $mailer;
        $this->tacheRepository = $tacheRepository;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    // ==================== 1. RÉINITIALISATION MOT DE PASSE ====================
    public function sendPasswordResetEmail(string $toEmail, string $code): bool
    {
        $subject = "Réinitialisation de votre mot de passe - Stratix";
        $body = $this->buildPasswordResetEmailBody($code);
        return $this->sendEmail($toEmail, $subject, $body);
    }

    // ==================== 2. CODE 2FA ====================
    public function send2FACode(string $toEmail, string $code): bool
    {
        $subject = "Code de vérification 2FA - Stratix";
        $body = $this->build2FAEmailBody($code);
        return $this->sendEmail($toEmail, $subject, $body);
    }

    // ==================== 3. EMAIL DE BIENVENUE ====================
    public function sendWelcomeEmail(string $toEmail, string $userName): bool
    {
        $subject = "Bienvenue sur Stratix!";
        $body = $this->buildWelcomeEmailBody($userName);
        return $this->sendEmail($toEmail, $subject, $body);
    }

    // ==================== 4. NOTIFICATION TÂCHE ====================
    public function sendTaskNotification(string $toEmail, string $taskTitle, string $taskDescription): bool
    {
        $subject = "📋 Nouvelle tâche assignée - Stratix";
        $body = $this->buildTaskEmailBody($taskTitle, $taskDescription);
        return $this->sendEmail($toEmail, $subject, $body);
    }

    // ==================== 5. NOTIFICATION PLANNING ====================
    public function sendPlanningNotification(string $toEmail, string $shiftType, string $date, string $hours): bool
    {
        $subject = "📅 Nouveau planning - Stratix";
        $body = $this->buildPlanningEmailBody($shiftType, $date, $hours);
        return $this->sendEmail($toEmail, $subject, $body);
    }

    // ==================== 6. NOTIFICATION PROJET ====================
    public function sendProjectUpdate(string $toEmail, string $projectName, string $message): bool
    {
        $subject = "📊 Mise à jour projet - " . $projectName;
        $body = $this->buildProjectEmailBody($projectName, $message);
        return $this->sendEmail($toEmail, $subject, $body);
    }

    // ==================== 7. RAPPEL TÂCHE ====================
    public function sendTaskReminder(int $tacheId, string $toEmail): bool
    {
        $tache = $this->tacheRepository->find($tacheId);
        if (!$tache) {
            return false;
        }

        $subject = "⏰ Rappel - Tâche à terminer - Stratix";
        $body = $this->buildTaskReminderBody($tache);
        return $this->sendEmail($toEmail, $subject, $body);
    }

    // ==================== MÉTHODE GÉNÉRIQUE D'ENVOI ====================
    private function sendEmail(string $toEmail, string $subject, string $body): bool
    {
        try {
            $email = (new Email())
                ->from(new Address(self::EMAIL_FROM, 'Stratix'))
                ->to($toEmail)
                ->subject($subject)
                ->html($body);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log("❌ Erreur email: " . $e->getMessage());
            return false;
        }
    }

    // ==================== TEMPLATES HTML ====================

    private function buildTaskEmailBody(string $taskTitle, string $taskDescription): string
    {
        return $this->getTemplate(
            "📋 STRATIX",
            "Nouvelle tâche assignée",
            "<div style='background-color: #F3F4F6; border-left: 4px solid #159895; padding: 20px; margin: 20px 0;'>
                <div style='font-size: 18px; font-weight: bold; color: #1F2937;'>" . htmlspecialchars($taskTitle) . "</div>
                <div style='color: #6B7280; margin-top: 10px;'>" . htmlspecialchars($taskDescription) . "</div>
            </div>
            <p style='color: #6B7280;'>Connectez-vous à l'application pour plus de détails.</p>"
        );
    }

    private function buildPlanningEmailBody(string $shiftType, string $date, string $hours): string
    {
        return $this->getTemplate(
            "📅 STRATIX",
            "Nouveau planning enregistré",
            "<div style='background-color: #EEF2FF; border-left: 4px solid #159895; padding: 20px; margin: 20px 0;'>
                <div style='font-size: 18px; font-weight: bold; color: #1F2937;'>Shift : " . htmlspecialchars($shiftType) . "</div>
                <div style='color: #6B7280; margin-top: 10px;'>📅 Date : " . htmlspecialchars($date) . "</div>
                <div style='color: #6B7280;'>⏰ Horaires : " . htmlspecialchars($hours) . "</div>
            </div>"
        );
    }

    private function buildProjectEmailBody(string $projectName, string $message): string
    {
        return $this->getTemplate(
            "📊 STRATIX",
            "Mise à jour projet : " . htmlspecialchars($projectName),
            "<div style='background-color: #F3F4F6; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                <p style='color: #4B5563;'>" . htmlspecialchars($message) . "</p>
            </div>"
        );
    }

    private function buildPasswordResetEmailBody(string $code): string
    {
        return $this->getTemplate(
            "🔐 STRATIX",
            "Réinitialisation de mot de passe",
            "<p>Vous avez demandé à réinitialiser votre mot de passe. Utilisez le code ci-dessous:</p>
            <div style='background-color: #F3F4F6; border: 2px dashed #159895; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;'>
                <div style='font-size: 32px; font-weight: bold; color: #159895; letter-spacing: 5px;'>" . htmlspecialchars($code) . "</div>
            </div>
            <p>Ce code est valable pendant <strong>1 heure</strong>.</p>
            <div style='background-color: #FEF3C7; border-left: 4px solid #F59E0B; padding: 15px; margin: 20px 0; color: #92400E;'>
                ⚠️ Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
            </div>"
        );
    }

    private function build2FAEmailBody(string $code): string
    {
        return $this->getTemplate(
            "🔒 STRATIX",
            "Code de vérification 2FA",
            "<p>Voici votre code de vérification pour vous connecter:</p>
            <div style='background-color: #EEF2FF; border: 2px solid #159895; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;'>
                <div style='font-size: 36px; font-weight: bold; color: #159895; letter-spacing: 8px;'>" . htmlspecialchars($code) . "</div>
            </div>
            <p>Ce code est valide pour cette session uniquement.</p>"
        );
    }

    private function buildWelcomeEmailBody(string $userName): string
    {
        return $this->getTemplate(
            "🎉 STRATIX",
            "Bienvenue " . htmlspecialchars($userName) . "!",
            "<p>Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter et profiter de toutes les fonctionnalités de Stratix.</p>
            <p>Nous sommes ravis de vous compter parmi nous!</p>"
        );
    }

    private function buildTaskReminderBody($tache): string
    {
        $statusLabels = [
            'A_FAIRE' => '📌 À faire',
            'EN_COURS' => '⚡ En cours',
            'TERMINEE' => '✅ Terminée'
        ];
        $status = $statusLabels[$tache->getStatut()] ?? $tache->getStatut();

        return $this->getTemplate(
            "⏰ STRATIX",
            "Rappel - Tâche à terminer",
            "<div style='background-color: #F3F4F6; border-left: 4px solid #F59E0B; padding: 20px; margin: 20px 0;'>
                <div style='font-size: 18px; font-weight: bold; color: #1F2937;'>" . htmlspecialchars($tache->getTitre()) . "</div>
                <div style='color: #6B7280; margin-top: 10px;'>📅 Deadline : " . ($tache->getDeadline()?->format('d/m/Y') ?? 'Non définie') . "</div>
                <div style='color: #6B7280;'>🎯 Priorité : " . htmlspecialchars($tache->getPriorite()) . "</div>
                <div style='color: #6B7280;'>📌 Statut : " . $status . "</div>
            </div>
            <p>N'oubliez pas de terminer cette tâche à temps !</p>"
        );
    }

    private function getTemplate(string $logo, string $title, string $content): string
    {
        return '<!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f5; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; border-radius: 10px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 32px; font-weight: bold; color: #159895; }
                .title { font-size: 24px; color: #1F2937; margin: 20px 0; }
                .footer { text-align: center; color: #9CA3AF; font-size: 12px; margin-top: 30px; border-top: 1px solid #E5E7EB; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">' . $logo . '</div>
                </div>
                <h2 class="title">' . $title . '</h2>
                ' . $content . '
                <div class="footer">
                    <p>© 2026 Stratix - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>';
    }
}