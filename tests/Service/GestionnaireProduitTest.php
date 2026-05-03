<?php

namespace App\Tests\Service;

use App\Entity\Produit;
use App\Service\GestionnaireProduit; 
use PHPUnit\Framework\TestCase;

class GestionnaireProduitTest extends TestCase
{
    /**
     * Teste qu'un produit avec des données valides est accepté
     */
    public function testProduitValide(): void
    {
        $produit = new Produit();
        $produit->setNom('ESP32 Microcontroller');
        $produit->setPrix(45.50);
        $produit->setStockActuel(10);

        $manager = new GestionnaireProduit();
        
        $this->assertTrue($manager->validate($produit));
    }

    /**
     * Teste qu'une exception est lancée si le nom est vide
     */
    public function testProduitSansNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du produit est obligatoire');

        $produit = new Produit();
        $produit->setNom(''); // Nom vide
        $produit->setPrix(20);

        $manager = new GestionnaireProduit();
        $manager->validate($produit);
    }

    /**
     * Teste qu'une exception est lancée si le prix est négatif
     */
    public function testProduitPrixInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix ne peut pas être négatif');

        $produit = new Produit();
        $produit->setNom('Capteur Humidité');
        $produit->setPrix(-10.0); // Prix négatif

        $manager = new GestionnaireProduit();
        $manager->validate($produit);
    }
}