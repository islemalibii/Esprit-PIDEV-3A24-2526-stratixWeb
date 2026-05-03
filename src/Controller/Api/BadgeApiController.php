<?php

namespace App\Controller\Api;

use App\Repository\TacheRepository;
use App\Repository\UserBadgeRepository;
use App\Repository\BadgeRepository;
use App\Service\BadgeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/badges')]
class BadgeApiController extends AbstractController
{
    public function __construct(
        private TacheRepository $tacheRepository,
        private BadgeService $badgeService,
        private UserBadgeRepository $userBadgeRepository,
        private BadgeRepository $badgeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/me', name: 'api_badges_me', methods: ['GET'])]
    public function getMyBadges(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $userId = $user->getId();
        
        $badges = $this->badgeService->getUserBadges($userId);
        $stats = $this->badgeService->getUserStats($userId);

        return $this->json([
            'success' => true,
            'badges' => $badges,
            'stats' => $stats
        ]);
    }

    #[Route('/check', name: 'api_badges_check', methods: ['POST'])]
    public function checkBadges(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $userId = $user->getId();
        
        $newBadges = $this->badgeService->checkAndAwardBadges($userId);
        
        $formattedBadges = [];
        foreach ($newBadges as $badge) {
            $formattedBadges[] = [
                'nom' => $badge->getNom(),
                'icone' => $badge->getIcone(),
                'description' => $badge->getDescription()
            ];
        }

        return $this->json([
            'success' => true,
            'new_badges' => $formattedBadges
        ]);
    }

    #[Route('/all-employees', name: 'api_badges_all_employees', methods: ['GET'])]
    public function getAllEmployeesBadges(): JsonResponse
    {
        // Récupérer tous les utilisateurs
        $userRepository = $this->entityManager->getRepository('App\Entity\Utilisateur');
        $users = $userRepository->findAll();
        
        $allTaches = $this->tacheRepository->findAll();
        $employeesData = [];
        
        foreach ($users as $user) {
            $userId = $user->getId();
            $userNom = $user->getNom();
            $userPrenom = $user->getPrenom();
            $userEmail = $user->getEmail();
            $userRole = $user->getRole();
            
            // Compter les tâches terminées
            $userTaches = array_filter($allTaches, fn($t) => $t->getEmployeId() === $userId);
            $terminees = count(array_filter($userTaches, fn($t) => $t->getStatut() === 'TERMINEE'));
            
            // Calculer les badges selon le nombre de tâches terminées
            $badges = [];
            $badgesList = [
                5 => ['nom' => '🥉 Débutant', 'icone' => '🥉', 'description' => '5 tâches terminées'],
                25 => ['nom' => '🥈 Intermédiaire', 'icone' => '🥈', 'description' => '25 tâches terminées'],
                50 => ['nom' => '🥇 Expert', 'icone' => '🥇', 'description' => '50 tâches terminées'],
                100 => ['nom' => '🏆 Champion', 'icone' => '🏆', 'description' => '100 tâches terminées'],
            ];
            
            foreach ($badgesList as $seuil => $badge) {
                if ($terminees >= $seuil) {
                    $badges[] = [
                        'nom' => $badge['nom'],
                        'icone' => $badge['icone'],
                        'description' => $badge['description'],
                        'obtenu_le' => date('d/m/Y')
                    ];
                }
            }
            
            // N'inclure que les employés qui ont au moins un badge
            if (count($badges) > 0) {
                $employeesData[] = [
                    'user' => [
                        'prenom' => $userPrenom,
                        'nom' => $userNom,
                        'email' => $userEmail,
                        'role' => $userRole
                    ],
                    'terminees' => $terminees,
                    'badges' => $badges,
                    'total_badges' => count($badges)
                ];
            }
        }
        
        // Trier par nombre de badges (du plus haut au plus bas)
        usort($employeesData, fn($a, $b) => $b['total_badges'] <=> $a['total_badges']);
        
        // Calculer les statistiques
        $totalBadges = array_sum(array_column($employeesData, 'total_badges'));
        $maxBadges = !empty($employeesData) ? max(array_column($employeesData, 'total_badges')) : 0;
        $avgBadges = !empty($employeesData) ? round($totalBadges / count($employeesData), 1) : 0;
        
        return $this->json([
            'success' => true,
            'employees' => $employeesData,
            'stats' => [
                'total_employees' => count($employeesData),
                'total_badges' => $totalBadges,
                'max_badges_per_employee' => $maxBadges,
                'avg_badges_per_employee' => $avgBadges
            ]
        ]);
    }
    
    #[Route('/init', name: 'api_badges_init', methods: ['POST'])]
    public function initBadges(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->badgeService->initBadges();
        
        return $this->json([
            'success' => true,
            'message' => 'Badges initialisés avec succès!'
        ]);
    }
}