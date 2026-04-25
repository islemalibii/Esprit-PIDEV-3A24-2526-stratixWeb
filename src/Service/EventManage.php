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

        if (strlen($evenement->getTitre()) < 5) {
            throw new \InvalidArgumentException('Le titre doit faire au moins 5 caracteres');
        }

        if (strlen($evenement->getTitre()) > 100) {
            throw new \InvalidArgumentException('Le titre ne peut pas dépasser 100 caracteres');
        }

        if (empty($evenement->getDescription())) {
            throw new \InvalidArgumentException('La description est obligatoire');
        }

        if (strlen($evenement->getDescription()) < 10) {
            throw new \InvalidArgumentException('La description est trop courte');
        }

        if ($evenement->getDateEvent() === null) {
            throw new \InvalidArgumentException('La date est obligatoire');
        }

        $today = new \DateTime('today');
        if ($evenement->getDateEvent() < $today) {
            throw new \InvalidArgumentException("La date de l'événement ne peut pas être dans le passé");
        }

        $minDate = new \DateTime('+3 days');
        if ($evenement->getDateEvent() < $minDate) {
            throw new \InvalidArgumentException("L'evenement doit etre planifie au moins 3 jours a l'avance");
        }

        if (empty($evenement->getLieu())) {
            throw new \InvalidArgumentException('Le lieu est obligatoire');
        }

        $validTypes = ['reunion', 'formation', 'lancementProduit', 'seminaire', 'recrutement'];
        if (empty($evenement->getTypeEvent())) {
            throw new \InvalidArgumentException("Le type d'événement est obligatoire");
        }
        if (!in_array($evenement->getTypeEvent(), $validTypes)) {
            throw new \InvalidArgumentException("Type d'événement invalide");
        }

        $validStatuts = ['planifier', 'annuler', 'terminer'];
        if (!in_array($evenement->getStatut(), $validStatuts)) {
            throw new \InvalidArgumentException('Statut invalide');
        }

        return true;
    }
}