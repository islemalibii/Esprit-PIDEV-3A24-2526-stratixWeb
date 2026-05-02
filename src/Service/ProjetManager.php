<?php

namespace App\Service;

use App\Entity\Projet;

class ProjetManager
{
    /**
     * Valide les règles métier fondamentales d'un projet.
     */
public function validate(Projet $projet): bool
{
    // Validation du nom
    if (empty($projet->getNom())) {
        throw new \InvalidArgumentException("Le nom du projet est obligatoire.");
    }

    // Validation des dates du projet
    if ($projet->getDateFin() < $projet->getDateDebut()) {
        throw new \InvalidArgumentException("La date de fin ne peut pas être antérieure à la date de début.");
    }

    $phases = $projet->getPhases();

    foreach ($phases as $i => $phaseA) {
        // 1. Vérification limites Projet vs Phase
        if ($phaseA->getDateDebut() < $projet->getDateDebut() || 
            $phaseA->getDateFin() > $projet->getDateFin()) {
            throw new \InvalidArgumentException("Les dates de la phase sont hors limites du projet.");
        }

        // 2. Vérification chevauchement entre phases
        foreach ($phases as $j => $phaseB) {
            if ($i === $j) continue;
            if ($phaseA->getDateDebut() < $phaseB->getDateFin() && 
                $phaseB->getDateDebut() < $phaseA->getDateFin()) {
                throw new \LogicException("Conflit de planning entre les phases.");
            }
        }

        if ($projet->getStatut() === 'Terminée') {
            foreach ($projet->getPhases() as $phase) {
                if ($phase->getStatut() !== 'Terminée') {
                     throw new \LogicException("Impossible de terminer le projet : la phase '" . $phase->getNom() . "' n'est pas encore finie.");
        }
    }
}
        
    }

    return true;
}}