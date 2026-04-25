<?php
// src/Service/PlanningManager.php

namespace App\Service;

use App\Entity\Planning;

class PlanningManager
{
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
}