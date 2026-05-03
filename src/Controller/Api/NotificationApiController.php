<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
class NotificationApiController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    // ==================== 1. RÉCUPÉRER MES NOTIFICATIONS ====================
    #[Route('/me', name: 'api_notifications_me', methods: ['GET'])]
    public function getMyNotifications(): JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $userId = method_exists($user, 'getId') ? $user->getId() : null;
        if (!is_int($userId)) {
            return $this->json(['error' => 'Utilisateur invalide'], 400);
        }

        $notifications = $this->notificationService->getUserNotifications($userId);

        return $this->json([
            'success' => true,
            'data'    => [
                'unread_count' => $notifications['unread_count'],
                'unread'       => array_map(fn(Notification $n) => $this->formatNotification($n), $notifications['unread']),
                'recent'       => array_map(fn(Notification $n) => $this->formatNotification($n), $notifications['recent']),
            ],
        ]);
    }

    // ==================== 2. MARQUER COMME LU ====================
    #[Route('/{id}/read', name: 'api_notifications_read', methods: ['PATCH'])]
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $userId = method_exists($user, 'getId') ? $user->getId() : null;
        if (!is_int($userId)) {
            return $this->json(['error' => 'Utilisateur invalide'], 400);
        }

        $success = $this->notificationService->markAsRead($id, $userId);

        return $this->json([
            'success' => $success,
            'message' => $success ? 'Notification marquée comme lue' : 'Notification non trouvée',
        ]);
    }

    // ==================== 3. TOUT MARQUER COMME LU ====================
    #[Route('/read-all', name: 'api_notifications_read_all', methods: ['PATCH'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json(['success' => true]);
    }

    // ==================== 4. FORCER LA VÉRIFICATION DES DEADLINES ====================
    #[Route('/check-deadlines', name: 'api_notifications_check', methods: ['POST'])]
    public function checkDeadlines(): JsonResponse
    {
        $this->notificationService->checkDeadlinesAndNotify();

        return $this->json([
            'success' => true,
            'message' => 'Vérification des deadlines effectuée',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNotification(Notification $notification): array
    {
        $createdAt = $notification->getCreatedAt();

        return [
            'id'          => $notification->getId(),
            'title'       => $notification->getTitle(),
            'message'     => $notification->getMessage(),
            'type'        => $notification->getType(),
            'isRead'      => $notification->isRead(),
            'createdAt'   => $createdAt !== null ? $createdAt->format('d/m/Y H:i:s') : null,
            'relatedId'   => $notification->getRelatedId(),
            'relatedType' => $notification->getRelatedType(),
        ];
    }
}