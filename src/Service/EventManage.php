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

    public function validateParticipation(Evenement $evenement): bool
    {
        if ($evenement->getStatut() === 'annuler') {
            throw new \InvalidArgumentException("Un événement annulé ne peut pas accepter de participations");
        }
        if ($evenement->getStatut() === 'terminer') {
            throw new \InvalidArgumentException("Un événement terminé ne peut pas accepter de participations");
        }
        return true;
    }


    public function validateRecurrence(Evenement $evenement): bool
    {
        $validRecurrences = ['none', 'weekly', 'monthly'];
        if (!in_array($evenement->getRecurrence(), $validRecurrences)) {
            throw new \InvalidArgumentException("Récurrence invalide");
        }
        return true;
    }


    public function validateImageUrl(Evenement $evenement): bool
    {
        $url = $evenement->getImageUrl();

        if ($url === null) {
            throw new \InvalidArgumentException("L'URL de l'image est obligatoire");
        }
        $validExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        if (!in_array($extension, $validExtensions)) {
            throw new \InvalidArgumentException("Format d'image invalide. Utilisez JPG, PNG ou WEBP");
        }
        return true;
    }
}