<?php
// src/Service/PlanningManager.php

namespace App\Service;

use App\Entity\Planning;

class PlanningManager
{
    // ========== VALIDATION DE BASE ==========
    
    public function validate(Planning $planning): bool
    {
        // Règle 1: Date obligatoire
        if ($planning->getDate() === null) {
            throw new \InvalidArgumentException('La date du planning est obligatoire.');
        }
        
        // Règle 2: Date dans le futur
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        if ($planning->getDate() < $today) {
            throw new \InvalidArgumentException('La date doit être aujourd\'hui ou dans le futur.');
        }
        
        // Règle 3: Type shift obligatoire
        if (empty($planning->getTypeShift())) {
            throw new \InvalidArgumentException('Le type de shift est obligatoire.');
        }
        
        // Règle 4: Type shift valide
        $validShifts = ['MATIN', 'SOIR', 'NUIT', 'RTT', 'CONGE'];
        if (!in_array($planning->getTypeShift(), $validShifts)) {
            throw new \InvalidArgumentException('Type de shift invalide. Utilisez MATIN, SOIR, NUIT, RTT ou CONGE.');
        }
        
        // Règle 5: Employé obligatoire
        if (empty($planning->getEmployeId())) {
            throw new \InvalidArgumentException('L\'employé assigné est obligatoire.');
        }
        
        return true;
    }
    
    // ========== GESTION DES SHIFTS ==========
    
    public function isShiftDeNuit(Planning $planning): bool
    {
        if ($planning->getHeureDebut() === null) {
            return false;
        }
        
        $heureDebut = (int) $planning->getHeureDebut()->format('H');
        
        return $heureDebut >= 20 || $heureDebut <= 5;
    }
    
    public function getHeuresSupplementaires(Planning $planning): int
    {
        if ($this->isCongesOuRTT($planning)) {
            return 0;
        }
        
        if ($planning->getHeureDebut() === null || $planning->getHeureFin() === null) {
            return 0;
        }
        
        $diff = $planning->getHeureDebut()->diff($planning->getHeureFin());
        $heuresTravaillees = $diff->h;
        $heuresNormales = 7;
        
        if ($heuresTravaillees <= $heuresNormales) {
            return 0;
        }
        
        return $heuresTravaillees - $heuresNormales;
    }
    
    public function isCongesOuRTT(Planning $planning): bool
    {
        return in_array($planning->getTypeShift(), ['RTT', 'CONGE']);
    }
    
    // ========== GESTION DES CONFLITS ==========
    
    public function estEnConflit(Planning $p1, Planning $p2): bool
    {
        if ($p1->getDate() && $p2->getDate() && 
            $p1->getDate()->format('Y-m-d') === $p2->getDate()->format('Y-m-d')) {
            
            if ($p1->getEmployeId() === $p2->getEmployeId()) {
                return true;
            }
        }
        
        return false;
    }
    
    // ========== TEMPS DE REPOS ==========
    
    public function getTempsRepos(Planning $p1, Planning $p2): ?int
    {
        if ($p1->getHeureFin() === null || $p2->getHeureDebut() === null) {
            return null;
        }
        
        $fin = clone $p1->getHeureFin();
        $debut = clone $p2->getHeureDebut();
        
        if ($p2->getDate() > $p1->getDate()) {
            $debut->modify('+1 day');
        }
        
        $diff = $fin->diff($debut);
        
        return $diff->h + ($diff->i / 60);
    }
    
    // ========== GESTION DES CONGÉS ==========
    
    public function sontCongesConsecutifs(Planning $p1, Planning $p2): bool
    {
        if (!$this->isCongesOuRTT($p1) || !$this->isCongesOuRTT($p2)) {
            return false;
        }
        
        $date1 = $p1->getDate();
        $date2 = $p2->getDate();
        
        if ($date1 === null || $date2 === null) {
            return false;
        }
        
        $diff = $date1->diff($date2)->days;
        
        return $diff === 1;
    }
    
    // ========== CONFORMITÉ LÉGALE ==========
    
    public function verifierConformiteLegale(Planning $planning): bool
    {
        if ($planning->getHeureDebut() === null || $planning->getHeureFin() === null) {
            return true;
        }
        
        $diff = $planning->getHeureDebut()->diff($planning->getHeureFin());
        $heuresTravaillees = $diff->h;
        
        // Durée maximale légale: 10h par jour
        return $heuresTravaillees <= 10;
    }
    
    // ========== DÉTECTION DES TROUS ==========
    
    public function detecterTrous(array $plannings): array
    {
        if (empty($plannings)) {
            return [];
        }
        
        // Trier par date
        usort($plannings, function($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });
        
        $trous = [];
        $datePrecedente = $plannings[0]->getDate();
        
        for ($i = 1; $i < count($plannings); $i++) {
            $dateCourante = $plannings[$i]->getDate();
            $diff = $datePrecedente->diff($dateCourante)->days;
            
            if ($diff > 1) {
                $dateDebutTrou = clone $datePrecedente;
                $dateDebutTrou->modify('+1 day');
                
                $dateFinTrou = clone $dateCourante;
                $dateFinTrou->modify('-1 day');
                
                $trous[] = [
                    'debut' => $dateDebutTrou->format('Y-m-d'),
                    'fin' => $dateFinTrou->format('Y-m-d'),
                    'jours' => $diff - 1
                ];
            }
            
            $datePrecedente = $dateCourante;
        }
        
        return $trous;
    }
    
    // ========== OPTIMISATION ==========
    
    public function optimiserRepartition(array $plannings): array
    {
        $chargeParEmploye = [];
        
        foreach ($plannings as $planning) {
            $employeId = $planning->getEmployeId();
            if (!isset($chargeParEmploye[$employeId])) {
                $chargeParEmploye[$employeId] = 0;
            }
            
            $chargeParEmploye[$employeId] += 1;
        }
        
        return [
            'charge_par_employe' => $chargeParEmploye,
            'total_shifts' => count($plannings),
            'employes_concernes' => count($chargeParEmploye)
        ];
    }
    
    // ========== ROTATION ==========
    
    public function genererRotation(array $employes, array $shifts, int $nbJours): array
    {
        $rotation = [];
        $indexEmploye = 0;
        $indexShift = 0;
        
        for ($jour = 1; $jour <= $nbJours; $jour++) {
            $rotation[] = [
                'jour' => $jour,
                'employe' => $employes[$indexEmploye % count($employes)],
                'shift' => $shifts[$indexShift % count($shifts)]
            ];
            
            $indexEmploye++;
            $indexShift++;
        }
        
        return $rotation;
    }
    
    // ========== PRÉDICTION ==========
    
    public function predireProchaineAbsence(array $historique, int $employeId): array
    {
        $absencesEmploye = array_filter($historique, function($absence) use ($employeId) {
            return $absence['employe'] === $employeId;
        });
        
        $nbAbsences = count($absencesEmploye);
        
        if ($nbAbsences < 2) {
            return [
                'probabilite' => 10,
                'message' => 'Pas assez de données pour prédire',
                'prochaine_prevue' => null
            ];
        }
        
        $dates = [];
        foreach ($absencesEmploye as $absence) {
            $dates[] = new \DateTime($absence['date']);
        }
        
        sort($dates);
        
        $intervalles = [];
        for ($i = 1; $i < count($dates); $i++) {
            $intervalles[] = $dates[$i]->diff($dates[$i-1])->days;
        }
        
        $intervalleMoyen = array_sum($intervalles) / count($intervalles);
        
        return [
            'probabilite' => min(80, $nbAbsences * 15),
            'intervalle_moyen_jours' => round($intervalleMoyen),
            'prochaine_prevue' => (new \DateTime())->modify("+{$intervalleMoyen} days")->format('Y-m-d')
        ];
    }
}