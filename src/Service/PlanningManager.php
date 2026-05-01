<?php
// src/Service/PlanningManager.php

namespace App\Service;

use App\Entity\Planning;

class PlanningManager
{
    // ========== VALIDATION DE BASE ==========
    
    public function validate(Planning $planning): bool
    {
        $date = $planning->getDate();
        if ($date === null) {
            throw new \InvalidArgumentException('La date du planning est obligatoire.');
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        if ($date < $today) {
            throw new \InvalidArgumentException('La date doit être aujourd\'hui ou dans le futur.');
        }
        
        $typeShift = $planning->getTypeShift();
        if ($typeShift === null || $typeShift === '') {
            throw new \InvalidArgumentException('Le type de shift est obligatoire.');
        }
        
        $validShifts = ['MATIN', 'SOIR', 'NUIT', 'RTT', 'CONGE'];
        if (!in_array($typeShift, $validShifts, true)) {
            throw new \InvalidArgumentException('Type de shift invalide. Utilisez MATIN, SOIR, NUIT, RTT ou CONGE.');
        }
        
        $employeId = $planning->getEmployeId();
        if ($employeId === null || $employeId === 0) {
            throw new \InvalidArgumentException('L\'employé assigné est obligatoire.');
        }
        
        return true;
    }
    
    // ========== GESTION DES SHIFTS ==========
    
    public function isShiftDeNuit(Planning $planning): bool
    {
        $heureDebut = $planning->getHeureDebut();
        if ($heureDebut === null) {
            return false;
        }
        
        $heure = (int) $heureDebut->format('H');
        
        return $heure >= 20 || $heure <= 5;
    }
    
    public function getHeuresSupplementaires(Planning $planning): int
    {
        if ($this->isCongesOuRTT($planning)) {
            return 0;
        }
        
        $heureDebut = $planning->getHeureDebut();
        $heureFin = $planning->getHeureFin();
        
        if ($heureDebut === null || $heureFin === null) {
            return 0;
        }
        
        $diff = $heureDebut->diff($heureFin);
        $heuresTravaillees = $diff->h;
        $heuresNormales = 7;
        
        if ($heuresTravaillees <= $heuresNormales) {
            return 0;
        }
        
        return $heuresTravaillees - $heuresNormales;
    }
    
    public function isCongesOuRTT(Planning $planning): bool
    {
        $typeShift = $planning->getTypeShift();
        return in_array($typeShift, ['RTT', 'CONGE'], true);
    }
    
    // ========== GESTION DES CONFLITS ==========
    
    public function estEnConflit(Planning $p1, Planning $p2): bool
    {
        $date1 = $p1->getDate();
        $date2 = $p2->getDate();
        
        if ($date1 !== null && $date2 !== null && 
            $date1->format('Y-m-d') === $date2->format('Y-m-d')) {
            
            if ($p1->getEmployeId() === $p2->getEmployeId()) {
                return true;
            }
        }
        
        return false;
    }
    
    // ========== TEMPS DE REPOS ==========
    
    public function getTempsRepos(Planning $p1, Planning $p2): ?int
    {
        $heureFin1 = $p1->getHeureFin();
        $heureDebut2 = $p2->getHeureDebut();
        
        if ($heureFin1 === null || $heureDebut2 === null) {
            return null;
        }
        
        $fin = clone $heureFin1;
        $debut = clone $heureDebut2;
        
        $date2 = $p2->getDate();
        $date1 = $p1->getDate();
        
        if ($date2 !== null && $date1 !== null && $date2 > $date1) {
            $debut = new \DateTime($debut->format('Y-m-d H:i:s'));
            $debut->modify('+1 day');
        }
        
        $diff = $fin->diff($debut);
        
        return $diff->h + (int)($diff->i / 60);
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
        $heureDebut = $planning->getHeureDebut();
        $heureFin = $planning->getHeureFin();
        
        if ($heureDebut === null || $heureFin === null) {
            return true;
        }
        
        $diff = $heureDebut->diff($heureFin);
        $heuresTravaillees = $diff->h;
        
        return $heuresTravaillees <= 10;
    }
    
    // ========== DÉTECTION DES TROUS ==========
    
    /**
     * @param Planning[] $plannings
     * @return array<int, array{debut: string, fin: string, jours: int}>
     */
    public function detecterTrous(array $plannings): array
    {
        if (empty($plannings)) {
            return [];
        }
        
        // Trier par date
        usort($plannings, function(Planning $a, Planning $b) {
            $dateA = $a->getDate();
            $dateB = $b->getDate();
            if ($dateA === null || $dateB === null) {
                return 0;
            }
            return $dateA <=> $dateB;
        });
        
        $trous = [];
        $premierPlanning = $plannings[0];
        $datePrecedente = $premierPlanning->getDate();
        
        if ($datePrecedente === null) {
            return [];
        }
        
        for ($i = 1; $i < count($plannings); $i++) {
            $planningCourant = $plannings[$i];
            $dateCourante = $planningCourant->getDate();
            
            if ($dateCourante === null) {
                continue;
            }
            
            $diff = $datePrecedente->diff($dateCourante)->days;
            
            if ($diff > 1) {
                $dateDebutTrou = new \DateTime($datePrecedente->format('Y-m-d'));
                $dateDebutTrou->modify('+1 day');
                
                $dateFinTrou = new \DateTime($dateCourante->format('Y-m-d'));
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
    
    /**
     * @param Planning[] $plannings
     * @return array{charge_par_employe: array<int, int>, total_shifts: int, employes_concernes: int}
     */
    public function optimiserRepartition(array $plannings): array
    {
        /** @var array<int, int> $chargeParEmploye */
        $chargeParEmploye = [];
        
        foreach ($plannings as $planning) {
            $employeId = $planning->getEmployeId();
            if ($employeId !== null) {
                if (!isset($chargeParEmploye[$employeId])) {
                    $chargeParEmploye[$employeId] = 0;
                }
                $chargeParEmploye[$employeId] += 1;
            }
        }
        
        return [
            'charge_par_employe' => $chargeParEmploye,
            'total_shifts' => count($plannings),
            'employes_concernes' => count($chargeParEmploye)
        ];
    }
    
    // ========== ROTATION ==========
    
    /**
     * @param array<int> $employes
     * @param array<string> $shifts
     * @return array<int, array{jour: int, employe: int, shift: string}>
     */
    public function genererRotation(array $employes, array $shifts, int $nbJours): array
    {
        $rotation = [];
        $indexEmploye = 0;
        $indexShift = 0;
        $nbEmployes = count($employes);
        $nbShifts = count($shifts);
        
        for ($jour = 1; $jour <= $nbJours; $jour++) {
            if ($nbEmployes > 0 && $nbShifts > 0) {
                $rotation[] = [
                    'jour' => $jour,
                    'employe' => $employes[$indexEmploye % $nbEmployes],
                    'shift' => $shifts[$indexShift % $nbShifts]
                ];
            }
            
            $indexEmploye++;
            $indexShift++;
        }
        
        return $rotation;
    }
    
    // ========== PRÉDICTION ==========
    
    /**
     * @param array<int, array{employe: int, date: string, type: string}> $historique
     * @return array{probabilite: int, message?: string, prochaine_prevue?: ?string, intervalle_moyen_jours?: float}
     */
    public function predireProchaineAbsence(array $historique, int $employeId): array
    {
        $absencesEmploye = array_filter($historique, function(array $absence) use ($employeId): bool {
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
        
        $prochainePrevue = null;
        if ($intervalleMoyen > 0) {
            $prochainePrevue = (new \DateTime())->modify("+{$intervalleMoyen} days")->format('Y-m-d');
        }
        
        return [
            'probabilite' => min(80, $nbAbsences * 15),
            'intervalle_moyen_jours' => round($intervalleMoyen),
            'prochaine_prevue' => $prochainePrevue
        ];
    }
}