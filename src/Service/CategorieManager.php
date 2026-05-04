<?php

namespace App\Service;

use App\Entity\CategorieService;

class CategorieManager
{
    public function validate(CategorieService $categorie): bool
    {
        $nom = $categorie->getNom();

        if ($nom === '') {
            throw new \InvalidArgumentException('Le nom de la catégorie est obligatoire.');
        }

        if (strlen($nom) < 3) {
            throw new \InvalidArgumentException('Le nom de la catégorie doit contenir au moins 3 caractères.');
        }

        return true;
    }
}