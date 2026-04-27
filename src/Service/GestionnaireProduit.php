<?php

namespace App\Service;

use App\Entity\Produit;

class GestionnaireProduit
{
    /**
     * Valide les règles métier d'un produit
     */
    public function validate(Produit $produit): bool
    {
        // 1. Vérification du nom
        if (empty($produit->getNom())) {
            throw new \InvalidArgumentException('Le nom du produit est obligatoire');
        }

        // 2. Vérification du prix
        if ($produit->getPrix() < 0) {
            throw new \InvalidArgumentException('Le prix ne peut pas être négatif');
        }

        return true;
    }
}