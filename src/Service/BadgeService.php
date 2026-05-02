<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\UserBadge;
use App\Repository\BadgeRepository;
use App\Repository\UserBadgeRepository;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;

class BadgeService
{
    private $badgesData = [
        ['nom' => '🥉 Débutant', 'icone' => '🥉', 'description' => 'Terminer 5 tâches', 'seuil' => 5, 'categorie' => 'taches'],
        ['nom' => '🥈 Intermédiaire', 'icone' => '🥈', 'description' => 'Terminer 25 tâches', 'seuil' => 25, 'categorie' => 'taches'],
        ['nom' => '🥇 Expert', 'icone' => '🥇', 'description' => 'Terminer 50 tâches', 'seuil' => 50, 'categorie' => 'taches'],
        ['nom' => '🏆 Champion', 'icone' => '🏆', 'description' => 'Terminer 100 tâches', 'seuil' => 100, 'categorie' => 'taches'],
        ['nom' => '⚡ Speed', 'icone' => '⚡', 'description' => 'Terminer 10 tâches en une semaine', 'seuil' => 10, 'categorie' => 'productivite'],
        ['nom' => '📅 Ponctuel', 'icone' => '📅', 'description' => '10 tâches terminées à temps', 'seuil' => 10, 'categorie' => 'ponctualite'],
    ];

    public function __construct(
        private BadgeRepository $badgeRepository,
        private UserBadgeRepository $userBadgeRepository,
        private TacheRepository $tacheRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function initBadges(): void
    {
        foreach ($this->badgesData as $b) {
            $existing = $this->badgeRepository->findOneBy(['nom' => $b['nom']]);
            if (!$existing) {
                $badge = new Badge();
                $badge->setNom($b['nom']);
                $badge->setIcone($b['icone']);
                $badge->setDescription($b['description']);
                $badge->setSeuil($b['seuil']);
                $badge->setCategorie($b['categorie']);
                $this->entityManager->persist($badge);
            }
        }
        $this->entityManager->flush();
    }

    public function checkAndAwardBadges(int $userId): array
    {
        $taches = $this->tacheRepository->findAll();
        $tachesEmploye = array_filter($taches, fn($t) => $t->getEmployeId() === $userId);
        $tachesTerminees = array_filter($tachesEmploye, fn($t) => $t->getStatut() === 'TERMINEE');
        $totalTerminees = count($tachesTerminees);

        $allBadges = $this->badgeRepository->findAllBadges();
        $newBadges = [];

        foreach ($allBadges as $badge) {
            $existing = $this->userBadgeRepository->findOneBy([
                'userId' => $userId,
                'badge' => $badge->getId()
            ]);
            
            if (!$existing && $totalTerminees >= $badge->getSeuil()) {
                $userBadge = new UserBadge();
                $userBadge->setUserId($userId);
                $userBadge->setBadge($badge);
                $this->entityManager->persist($userBadge);
                $newBadges[] = $badge;
            }
        }
        
        $this->entityManager->flush();
        return $newBadges;
    }

    public function getUserBadges(int $userId): array
    {
        $userBadges = $this->userBadgeRepository->findUserBadges($userId);
        $result = [];
        
        foreach ($userBadges as $ub) {
            $result[] = [
                'id' => $ub->getId(),
                'nom' => $ub->getBadge()->getNom(),
                'icone' => $ub->getBadge()->getIcone(),
                'description' => $ub->getBadge()->getDescription(),
                'obtenu_le' => $ub->getObtenuLe()->format('d/m/Y')
            ];
        }
        
        return $result;
    }

    public function getUserStats(int $userId): array
    {
        $taches = $this->tacheRepository->findAll();
        $tachesEmploye = array_filter($taches, fn($t) => $t->getEmployeId() === $userId);
        $total = count($tachesEmploye);
        $terminees = count(array_filter($tachesEmploye, fn($t) => $t->getStatut() === 'TERMINEE'));
        $badgesCount = count($this->userBadgeRepository->findUserBadges($userId));
        
        return [
            'total_taches' => $total,
            'taches_terminees' => $terminees,
            'badges_obtenus' => $badgesCount,
            'prochain_badge' => $this->getNextBadge($terminees)
        ];
    }

    private function getNextBadge(int $terminees): ?array
    {
        $badges = $this->badgeRepository->findAllBadges();
        foreach ($badges as $badge) {
            if ($terminees < $badge->getSeuil()) {
                return [
                    'nom' => $badge->getNom(),
                    'seuil' => $badge->getSeuil(),
                    'reste' => $badge->getSeuil() - $terminees
                ];
            }
        }
        return null;
    }
}