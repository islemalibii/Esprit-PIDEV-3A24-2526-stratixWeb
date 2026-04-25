<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Tache;
use App\Entity\Utilisateur;
use App\Repository\NotificationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Notification\Notification as NotifierNotification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private MailerInterface $mailer,
        private NotifierInterface $notifier,
        private UtilisateurRepository $utilisateurRepository,
        private HttpClientInterface $httpClient
    ) {}

    // ==================== 1. CRÉER UNE NOTIFICATION ====================
    public function createNotification(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        ?int $relatedId = null,
        ?string $relatedType = null
    ): Notification {
        $notification = new Notification();
        $notification->setUserId($userId);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setType($type);
        $notification->setRelatedId($relatedId);
        $notification->setRelatedType($relatedType);
        
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
        
        // Envoyer la notification temps réel via Mercure
        $this->sendRealtimeNotification($userId, $notification);
        
        return $notification;
    }

    // ==================== 2. NOTIFICATION POUR DÉLAI PROCHES ====================
    public function checkDeadlinesAndNotify(): void
    {
        $taches = $this->entityManager
            ->getRepository(Tache::class)
            ->findAll();
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        foreach ($taches as $tache) {
            if (!$tache->getDeadline() || $tache->getStatut() === 'TERMINEE') {
                continue;
            }
            
            $employeId = $tache->getEmployeId();
            if (!$employeId) continue;
            
            $diff = $today->diff($tache->getDeadline())->days;
            $deadlineDate = $tache->getDeadline()->format('d/m/Y');
            
            // Vérifier si déjà notifié pour cette tâche
            $existingNotif = $this->notificationRepository->findOneBy([
                'relatedId' => $tache->getId(),
                'relatedType' => 'tache',
                'userId' => $employeId
            ]);
            
            if ($existingNotif) continue;
            
            if ($diff == 3) {
                $this->createNotification(
                    $employeId,
                    '⏰ Deadline dans 3 jours',
                    "La tâche '{$tache->getTitre()}' doit être terminée le {$deadlineDate}",
                    'warning',
                    $tache->getId(),
                    'tache'
                );
                $this->sendEmailNotification($employeId, "Deadline approche", $tache->getTitre(), $deadlineDate);
            }
            
            if ($diff == 1) {
                $this->createNotification(
                    $employeId,
                    '⚠️ Deadline DEMAIN !',
                    "URGENT : La tâche '{$tache->getTitre()}' est à rendre demain !",
                    'danger',
                    $tache->getId(),
                    'tache'
                );
                $this->sendEmailNotification($employeId, "URGENT - Deadline demain", $tache->getTitre(), $deadlineDate);
            }
            
            if ($tache->getDeadline() < $today && $tache->getStatut() !== 'TERMINEE') {
                // Vérifier si déjà notifié pour retard
                $existingRetard = $this->notificationRepository->findOneBy([
                    'relatedId' => $tache->getId(),
                    'title' => '❌ Tâche en retard'
                ]);
                if (!$existingRetard) {
                    $this->createNotification(
                        $employeId,
                        '❌ Tâche en retard',
                        "La tâche '{$tache->getTitre()}' est en retard ! Date limite : {$deadlineDate}",
                        'danger',
                        $tache->getId(),
                        'tache'
                    );
                    $this->sendEmailNotification($employeId, "Tâche en retard", $tache->getTitre(), $deadlineDate);
                }
            }
        }
    }

    // ==================== 3. NOTIFICATION POUR NOUVELLE TÂCHE ====================
    public function notifyNewTask(Tache $tache): void
    {
        $employeId = $tache->getEmployeId();
        if ($employeId) {
            $this->createNotification(
                $employeId,
                '📋 Nouvelle tâche assignée',
                "Vous avez une nouvelle tâche : '{$tache->getTitre()}'",
                'success',
                $tache->getId(),
                'tache'
            );
            $this->sendEmailNotification($employeId, "Nouvelle tâche", $tache->getTitre(), null);
        }
        
        // Notifier aussi l'admin
        $admins = $this->utilisateurRepository->findByRole('ROLE_ADMIN');
        foreach ($admins as $admin) {
            $this->createNotification(
                $admin->getId(),
                '📋 Nouvelle tâche créée',
                "Une nouvelle tâche '{$tache->getTitre()}' a été créée",
                'info',
                $tache->getId(),
                'tache'
            );
        }
    }

    // ==================== 4. NOTIFICATION TÂCHE TERMINÉE ====================
    public function notifyTaskCompleted(Tache $tache): void
    {
        $admins = $this->utilisateurRepository->findByRole('ROLE_ADMIN');
        foreach ($admins as $admin) {
            $this->createNotification(
                $admin->getId(),
                '✅ Tâche terminée',
                "La tâche '{$tache->getTitre()}' a été marquée comme terminée",
                'success',
                $tache->getId(),
                'tache'
            );
        }
    }

    // ==================== 5. NOTIFICATION MASSE (RUPTURE STOCK, ETC) ====================
    public function notifyAllAdmins(string $title, string $message, string $type = 'warning'): void
    {
        $admins = $this->utilisateurRepository->findByRole('ROLE_ADMIN');
        foreach ($admins as $admin) {
            $this->createNotification(
                $admin->getId(),
                $title,
                $message,
                $type
            );
        }
    }

    // ==================== 6. ENVOI EMAIL ====================
    private function sendEmailNotification(int $userId, string $subject, string $taskTitle, ?string $deadline): void
    {
        $user = $this->utilisateurRepository->find($userId);
        if (!$user || !$user->getEmail()) return;
        
        $email = (new Email())
            ->from('noreply@stratix.com')
            ->to($user->getEmail())
            ->subject('[STRATIX] ' . $subject)
            ->html($this->generateEmailHtml($subject, $taskTitle, $deadline, $user->getPrenom()));
        
        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log erreur mais continue
        }
    }

    private function generateEmailHtml(string $subject, string $taskTitle, ?string $deadline, string $userName): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head><style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1f3b4c; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f5f5f5; }
            .footer { text-align: center; padding: 10px; font-size: 12px; color: #666; }
            .button { background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style></head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>STRATIX</h2>
                    <p>Gestion des tâches et plannings</p>
                </div>
                <div class='content'>
                    <h3>Bonjour {$userName},</h3>
                    <p><strong>{$subject}</strong></p>
                    <p>Tâche : <strong>{$taskTitle}</strong></p>
                    " . ($deadline ? "<p>Date limite : <strong>{$deadline}</strong></p>" : "") . "
                    <p>Connectez-vous à votre espace STRATIX pour plus de détails.</p>
                    <a href='http://localhost:8000/tache' class='button'>Voir mes tâches</a>
                </div>
                <div class='footer'>
                    <p>© 2026 STRATIX - Application de gestion</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    // ==================== 7. NOTIFICATION TEMPS RÉEL (MERCURE) ====================
    private function sendRealtimeNotification(int $userId, Notification $notification): void
    {
        $mercureHubUrl = 'http://localhost:3000/.well-known/mercure';
        $topic = 'http://localhost:8000/notifications/' . $userId;
        
        $data = json_encode([
            'id' => $notification->getId(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'type' => $notification->getType(),
            'createdAt' => $notification->getCreatedAt()->format('H:i:s'),
            'isRead' => $notification->isRead()
        ]);
        
        try {
            $this->httpClient->request('POST', $mercureHubUrl, [
                'body' => http_build_query([
                    'topic' => $topic,
                    'data' => $data
                ])
            ]);
        } catch (\Exception $e) {
            // Mercure pas configuré, on ignore
        }
    }

    // ==================== 8. MARQUER COMME LU ====================
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = $this->notificationRepository->find($notificationId);
        if (!$notification || $notification->getUserId() !== $userId) {
            return false;
        }
        
        $notification->setIsRead(true);
        $this->entityManager->flush();
        return true;
    }

    // ==================== 9. RÉCUPÉRER LES NOTIFICATIONS ====================
    public function getUserNotifications(int $userId): array
    {
        $unread = $this->notificationRepository->findUnreadByUser($userId);
        $recent = $this->notificationRepository->findRecentByUser($userId, 20);
        
        return [
            'unread' => $unread,
            'unread_count' => count($unread),
            'recent' => $recent
        ];
    }
}