<?php

namespace App\Tests\Service;

use App\Entity\CategorieService;
use App\Service\CategorieManager;
use PHPUnit\Framework\TestCase;

class CategorieManagerTest extends TestCase
{
    public function testValidCategorie()
    {
        $categorie = new CategorieService();
        $categorie->setNom('Développement');

        $manager = new CategorieManager();

        $this->assertTrue($manager->validate($categorie));
    }

    public function testCategorieWithNullName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la catégorie est obligatoire.');

        $categorie = new CategorieService();

        $manager = new CategorieManager();
        $manager->validate($categorie);
    }

    public function testCategorieWithEmptyName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la catégorie est obligatoire.');

        $categorie = new CategorieService();
        $categorie->setNom('');

        $manager = new CategorieManager();
        $manager->validate($categorie);
    }

    public function testCategorieWithShortName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la catégorie doit contenir au moins 3 caractères.');

        $categorie = new CategorieService();
        $categorie->setNom('AB');

        $manager = new CategorieManager();
        $manager->validate($categorie);
    }

    public function testCategorieWithNameJustSpaces()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la catégorie est obligatoire.');

        $categorie = new CategorieService();
        $categorie->setNom('   ');

        $manager = new CategorieManager();
        $manager->validate($categorie);
    }
}