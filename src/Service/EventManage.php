<?php
// src/Service/EvenementManager.php

namespace App\Service;

use App\Entity\Evenement;

class EventManage
{
    public function validate(Evenement $evenement): bool
    {
        if (empty($evenement->getTitre())) {
            throw new \InvalidArgumentException('Le titre est obligatoire');
        }

       

        if (empty($evenement->getDescription())) {
            throw new \InvalidArgumentException('La description est obligatoire');
        }

        if ($evenement->getDateEvent() === null) {
            throw new \InvalidArgumentException('La date est obligatoire');
        }

        $today = new \DateTime('today');
        if ($evenement->getDateEvent() < $today) {
            throw new \InvalidArgumentException("La date de l'événement ne peut pas être dans le passé");
        }

        

        return true;
    }
}