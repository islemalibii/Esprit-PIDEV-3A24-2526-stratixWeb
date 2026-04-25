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
        // Règle 1 : Le nom du projet ne peut pas être vide [cite: 116]
        if (empty($projet->getNom())) {
            throw new \InvalidArgumentException('Le nom du projet est obligatoire.');
        }

        // Règle 2 : La date de fin doit être postérieure à la date de début [cite: 115]
        if ($projet->getDateFin() <= $projet->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        return true;
    }
}