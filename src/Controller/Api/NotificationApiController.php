<?php

namespace App\Controller\Api;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        
        $notifications = $this->notificationService->getUserNotifications($user->getId());
        
        return $this->json([
            'success' => true,
            'data' => [
                'unread_count' => $notifications['unread_count'],
                'unread' => array_map(fn($n) => $this->formatNotification($n), $notifications['unread']),
                'recent' => array_map(fn($n) => $this->formatNotification($n), $notifications['recent'])
            ]
        ]);
    }

    // ==================== 2. MARQUER COMME LU ====================
    #[Route('/{id}/read', name: 'api_notifications_read', methods: ['PATCH'])]
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        
        $success = $this->notificationService->markAsRead($id, $user->getId());
        
        return $this->json([
            'success' => $success,
            'message' => $success ? 'Notification marquée comme lue' : 'Notification non trouvée'
        ]);
    }

    // ==================== 3. TOUT MARQUER COMME LU ====================
    #[Route('/read-all', name: 'api_notifications_read_all', methods: ['PATCH'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        
        // Logique à implémenter
        return $this->json(['success' => true]);
    }

    // ==================== 4. FORCER LA VÉRIFICATION DES DEADLINES ====================
    #[Route('/check-deadlines', name: 'api_notifications_check', methods: ['POST'])]
    public function checkDeadlines(): JsonResponse
    {
        $this->notificationService->checkDeadlinesAndNotify();
        
        return $this->json([
            'success' => true,
            'message' => 'Vérification des deadlines effectuée'
        ]);
    }

    private function formatNotification($notification): array
    {
        return [
            'id' => $notification->getId(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'type' => $notification->getType(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()->format('d/m/Y H:i:s'),
            'relatedId' => $notification->getRelatedId(),
            'relatedType' => $notification->getRelatedType()
        ];
    }
}