<?php
// src/Service/TacheManager.php

namespace App\Service;

use App\Entity\Tache;

class TacheManager
{
    // ========== VALIDATION DE BASE ==========
    
    public function validate(Tache $tache): bool
    {
        // Règle 1: Titre obligatoire
        if (empty($tache->getTitre())) {
            throw new \InvalidArgumentException('Le titre de la tâche est obligatoire.');
        }
        
        // Règle 2: Titre >= 3 caractères
        if (strlen($tache->getTitre()) < 3) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 3 caractères.');
        }
        
        // Règle 3: Description obligatoire
        if (empty($tache->getDescription())) {
            throw new \InvalidArgumentException('La description de la tâche est obligatoire.');
        }
        
        // Règle 4: Deadline obligatoire
        if ($tache->getDeadline() === null) {
            throw new \InvalidArgumentException('La deadline est obligatoire.');
        }
        
        // Règle 5: Deadline dans le futur
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        if ($tache->getDeadline() < $today) {
            throw new \InvalidArgumentException('La deadline doit être aujourd\'hui ou dans le futur.');
        }
        
        // Règle 6: Statut valide
        $validStatuts = ['A_FAIRE', 'EN_COURS', 'TERMINEE'];
        if (!in_array($tache->getStatut(), $validStatuts)) {
            throw new \InvalidArgumentException('Statut invalide. Utilisez A_FAIRE, EN_COURS ou TERMINEE.');
        }
        
        // Règle 7: Priorité valide
        $validPriorites = ['HAUTE', 'MOYENNE', 'BASSE'];
        if (!in_array($tache->getPriorite(), $validPriorites)) {
            throw new \InvalidArgumentException('Priorité invalide. Utilisez HAUTE, MOYENNE ou BASSE.');
        }
        
        // Règle 8: Employé obligatoire
        if (empty($tache->getEmployeId())) {
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
        
        if ($tache->getDeadline() === null) {
            return false;
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        return $tache->getDeadline() < $today;
    }
    
    public function getJoursRetard(Tache $tache): int
    {
        if (!$this->isEnRetard($tache)) {
            return 0;
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $deadline = clone $tache->getDeadline();
        $deadline->setTime(0, 0, 0);
        
        // Calcul du nombre de jours de retard
        $diff = $deadline->diff($today);
        
        return (int) $diff->days;
    }
    
    public function getJoursRestants(Tache $tache): ?int
    {
        if ($tache->getDeadline() === null || $tache->getStatut() === 'TERMINEE') {
            return null;
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $deadline = clone $tache->getDeadline();
        $deadline->setTime(0, 0, 0);
        
        $diff = $today->diff($deadline);
        
        return (int) $diff->format('%r%a');
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
        
        if (!in_array($nouveauStatut, $validStatuts)) {
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
        
        return round(($terminees / $total) * 100);
    }
    
    // ========== SCORE DE COMPLEXITÉ ==========
    
    public function calculerScoreComplexite(Tache $tache): int
    {
        $score = 0;
        
        $score += min(strlen($tache->getTitre()) * 2, 20);
        $score += min(strlen($tache->getDescription() ?? '') / 5, 30);
        
        if ($tache->getPriorite() === 'HAUTE') {
            $score += 30;
        } elseif ($tache->getPriorite() === 'MOYENNE') {
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
        
        return max(1, (int)($base * ($complexite / 50)));
    }
    
    // ========== BURNDOWN CHART ==========
    
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
            'progression' => $totalEstime > 0 ? round(($totalPasse / $totalEstime) * 100) : 0
        ];
    }
    
    // ========== DÉPENDANCES CIRCULAIRES ==========
    
    public function detecterDependancesCirculaires(array $dependances): bool
    {
        $visite = [];
        $enCours = [];
        
        $dfs = function($node) use (&$dfs, &$visite, &$enCours, $dependances) {
            $visite[$node] = true;
            $enCours[$node] = true;
            
            foreach ($dependances[$node] ?? [] as $voisin) {
                if (!isset($visite[$voisin])) {
                    if ($dfs($voisin)) return true;
                } elseif (isset($enCours[$voisin])) {
                    return true;
                }
            }
            
            unset($enCours[$node]);
            return false;
        };
        
        foreach (array_keys($dependances) as $node) {
            if (!isset($visite[$node])) {
                if ($dfs($node)) return true;
            }
        }
        
        return false;
    }
}