<?php
// src/Service/TacheManager.php

namespace App\Service;

use App\Entity\Tache;

class TacheManager
{
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
}