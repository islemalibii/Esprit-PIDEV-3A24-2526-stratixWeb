<?php
// src/Service/TacheManager.php

namespace App\Service;

use App\Entity\Tache;

class TacheManager
{
    // ========== VALIDATION DE BASE ==========
    
    public function validate(Tache $tache): bool
    {
        $titre = $tache->getTitre();
        if ($titre === null || $titre === '') {
            throw new \InvalidArgumentException('Le titre de la tâche est obligatoire.');
        }
        
        if (strlen($titre) < 3) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 3 caractères.');
        }
        
        $description = $tache->getDescription();
        if ($description === null || $description === '') {
            throw new \InvalidArgumentException('La description de la tâche est obligatoire.');
        }
        
        $deadline = $tache->getDeadline();
        if ($deadline === null) {
            throw new \InvalidArgumentException('La deadline est obligatoire.');
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        if ($deadline < $today) {
            throw new \InvalidArgumentException('La deadline doit être aujourd\'hui ou dans le futur.');
        }
        
        $statut = $tache->getStatut();
        $validStatuts = ['A_FAIRE', 'EN_COURS', 'TERMINEE'];
        if (!in_array($statut, $validStatuts, true)) {
            throw new \InvalidArgumentException('Statut invalide. Utilisez A_FAIRE, EN_COURS ou TERMINEE.');
        }
        
        $priorite = $tache->getPriorite();
        $validPriorites = ['HAUTE', 'MOYENNE', 'BASSE'];
        if (!in_array($priorite, $validPriorites, true)) {
            throw new \InvalidArgumentException('Priorité invalide. Utilisez HAUTE, MOYENNE ou BASSE.');
        }
        
        $employeId = $tache->getEmployeId();
        if ($employeId === null || $employeId === 0) {
            throw new \InvalidArgumentException('L\'employé assigné est obligatoire.');
        }
        
        return true;
    }
    
    // ========== GESTION DU RETARD ==========
    
    public function isEnRetard(Tache $tache): bool
    {
        if ($tache->getStatut() === 'TERMINEE') {
            return false;
        }
        
        $deadline = $tache->getDeadline();
        if ($deadline === null) {
            return false;
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        return $deadline < $today;
    }
    
    public function getJoursRetard(Tache $tache): int
    {
        if (!$this->isEnRetard($tache)) {
            return 0;
        }
        
        $deadline = $tache->getDeadline();
        if ($deadline === null) {
            return 0;
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        $interval = $deadline->diff($today);
        return (int) $interval->days;
    }
    
    public function getJoursRestants(Tache $tache): ?int
    {
        $deadline = $tache->getDeadline();
        if ($deadline === null || $tache->getStatut() === 'TERMINEE') {
            return null;
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        if ($deadline < $today) {
            return -((int) $deadline->diff($today)->days);
        }
        
        return (int) $today->diff($deadline)->days;
    }
    
    // ========== MATRICE DE PRIORITÉ ==========
    
    public function isUrgente(Tache $tache): bool
    {
        if ($tache->getPriorite() !== 'HAUTE') {
            return false;
        }
        
        $joursRestants = $this->getJoursRestants($tache);
        
        return $joursRestants !== null && $joursRestants < 3;
    }
    
    public function getMatricePriorite(Tache $tache): string
    {
        $estUrgent = $this->isUrgente($tache);
        $estImportant = $tache->getPriorite() === 'HAUTE';
        
        if ($estUrgent && $estImportant) return 'URGENT_IMPORTANT';
        if (!$estUrgent && $estImportant) return 'NON_URGENT_IMPORTANT';
        if ($estUrgent && !$estImportant) return 'URGENT_NON_IMPORTANT';
        return 'NON_URGENT_NON_IMPORTANT';
    }
    
    public function getRecommandation(Tache $tache): string
    {
        $matrice = $this->getMatricePriorite($tache);
        
        $recommandations = [
            'URGENT_IMPORTANT' => '🚨 À faire immédiatement (priorité absolue)',
            'NON_URGENT_IMPORTANT' => '📅 Planifier dans le sprint suivant',
            'URGENT_NON_IMPORTANT' => '🤝 Déléguer si possible',
            'NON_URGENT_NON_IMPORTANT' => '🗂️ À faire en dernier ou à archiver'
        ];
        
        return $recommandations[$matrice] ?? 'Priorité à définir';
    }
    
    // ========== GESTION DE LA DÉLÉGATION ==========
    
    public function estDelegable(Tache $tache): bool
    {
        if ($tache->getPriorite() === 'HAUTE') {
            return false;
        }
        
        $joursRestants = $this->getJoursRestants($tache);
        
        return $joursRestants === null || $joursRestants >= 3;
    }
    
    // ========== CHANGEMENT DE STATUT ==========
    
    public function changerStatut(Tache $tache, string $nouveauStatut): Tache
    {
        $validStatuts = ['A_FAIRE', 'EN_COURS', 'TERMINEE'];
        
        if (!in_array($nouveauStatut, $validStatuts, true)) {
            throw new \InvalidArgumentException('Statut invalide.');
        }
        
        $tache->setStatut($nouveauStatut);
        
        return $tache;
    }
    
    // ========== PROGRESSION ==========
    
    public function calculerProgression(int $terminees, int $total): int
    {
        if ($total === 0) {
            return 0;
        }
        
        return (int) round(($terminees / $total) * 100);
    }
    
    // ========== SCORE DE COMPLEXITÉ ==========
    
    public function calculerScoreComplexite(Tache $tache): int
    {
        $score = 0;
        
        $titre = $tache->getTitre() ?? '';
        $score += min(strlen($titre) * 2, 20);
        
        $description = $tache->getDescription() ?? '';
        $score += min(strlen($description) / 5, 30);
        
        $priorite = $tache->getPriorite();
        if ($priorite === 'HAUTE') {
            $score += 30;
        } elseif ($priorite === 'MOYENNE') {
            $score += 15;
        } else {
            $score += 5;
        }
        
        return min(100, max(1, (int)$score));
    }
    
    // ========== ESTIMATION DE CHARGE ==========
    
    public function estimerCharge(Tache $tache): int
    {
        $base = 8;
        $complexite = $this->calculerScoreComplexite($tache);
        
        return max(1, (int)($base * ((float)$complexite / 60.0)));
    }
    
    // ========== BURNDOWN CHART ==========
    
    /**
     * @param array<Tache> $taches
     * @return array<string, int>
     */
    public function calculerBurndown(array $taches): array
    {
        $totalEstime = 0;
        
        foreach ($taches as $tache) {
            $totalEstime += $this->estimerCharge($tache);
        }
        
        $totalPasse = (int)($totalEstime * 0.7);
        
        return [
            'total_estime' => $totalEstime,
            'total_passe' => $totalPasse,
            'variance' => $totalEstime - $totalPasse,
            'progression' => $totalEstime > 0 ? (int) round(($totalPasse / $totalEstime) * 100) : 0
        ];
    }
}