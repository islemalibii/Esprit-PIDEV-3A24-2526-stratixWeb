<?php
// src/Service/EmailService.php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(private MailerInterface $mailer) {}

    public function sendTaskReminder(int $taskId, string $to): bool
    {
        try {
            $email = (new Email())
                ->from('no-reply@stratix.com')
                ->to($to)
                ->subject('Rappel de tâche')
                ->html("<p>Rappel pour la tâche #$taskId.</p>");
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendTaskNotification(string $to, string $title, string $description): bool
    {
        try {
            $email = (new Email())
                ->from('no-reply@stratix.com')
                ->to($to)
                ->subject("Nouvelle tâche : $title")
                ->html("<p><strong>$title</strong></p><p>$description</p>");
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendPlanningNotification(string $to, string $shiftType, string $date, string $hours): bool
    {
        try {
            $email = (new Email())
                ->from('no-reply@stratix.com')
                ->to($to)
                ->subject("Planning : $shiftType le $date")
                ->html("<p>Votre shift <strong>$shiftType</strong> est prévu le <strong>$date</strong> de <strong>$hours</strong>.</p>");
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendWelcomeEmail(string $to, string $name): bool
    {
        try {
            $email = (new Email())
                ->from('no-reply@stratix.com')
                ->to($to)
                ->subject("Bienvenue $name !")
                ->html("<p>Bonjour <strong>$name</strong>, bienvenue sur Stratix !</p>");
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendPasswordResetEmail(string $to, string $code): bool
    {
        try {
            $email = (new Email())
                ->from('no-reply@stratix.com')
                ->to($to)
                ->subject('Réinitialisation de mot de passe')
                ->html("<p>Votre code de réinitialisation : <strong>$code</strong></p>");
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}