<?php

namespace App\Service;

use App\Entity\Service;

class ServiceManager
{
    public function validate(Service $service): bool
    {
        if ($service->getBudget() === null || $service->getBudget() <= 0) {
            throw new \InvalidArgumentException('Le budget doit être supérieur à zéro.');
        }

        $dateDebut = $service->getDateDebut();
        $dateFin = $service->getDateFin();

        if ($dateDebut !== null && $dateFin !== null && $dateFin <= $dateDebut) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        return true;
    }
}