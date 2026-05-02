<?php

namespace App\Tests\Service;

use App\Entity\CategorieService;
use App\Entity\Service;
use App\Service\CategorieManager;
use PHPUnit\Framework\TestCase;

class CategorieManagerTest extends TestCase
{
    private function createCategorieWithName(string $name): CategorieService
    {
        $categorie = new CategorieService();
        $categorie->setNom($name);
        return $categorie;
    }
    
    private function createServiceForCategorie(CategorieService $categorie, string $title): Service
    {
        $service = new Service();
        $service->setTitre($title);
        $service->setCategorie($categorie);
        return $service;
    }

    public function testValidCategorie(): void
    {
        $categorie = $this->createCategorieWithName('Développement');
        $manager = new CategorieManager();
        $this->assertTrue($manager->validate($categorie));
    }

    public function testCategorieWithNullName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la catégorie est obligatoire.');
        $categorie = new CategorieService();
        $manager = new CategorieManager();
        $manager->validate($categorie);
    }

    public function testCategorieWithEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $categorie = $this->createCategorieWithName('');
        $manager = new CategorieManager();
        $manager->validate($categorie);
    }

    public function testCategorieWithShortName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $categorie = $this->createCategorieWithName('AB');
        $manager = new CategorieManager();
        $manager->validate($categorie);
    }

    public function testCategorieWithNameJustSpaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $categorie = $this->createCategorieWithName('   ');
        $manager = new CategorieManager();
        $manager->validate($categorie);
    }

    public function testCountServicesInCategory(): void
    {
        $categorie = $this->createCategorieWithName('Développement');
        $service1 = $this->createServiceForCategorie($categorie, 'Service 1');
        $service2 = $this->createServiceForCategorie($categorie, 'Service 2');
        
        $categorie->addService($service1);
        $categorie->addService($service2);
        
        $this->assertCount(2, $categorie->getServices());
    }

    public function testCategoryNameCaseInsensitive(): void
    {
        $categorie1 = $this->createCategorieWithName('Développement');
        $categorie2 = $this->createCategorieWithName('développement');
        
        // FIX: Add null coalescing operator
        $this->assertEquals(
            strtolower($categorie1->getNom() ?? ''),
            strtolower($categorie2->getNom() ?? '')
        );
    }

    public function testRemoveServiceFromCategory(): void
    {
        $categorie = $this->createCategorieWithName('Développement');
        $service = $this->createServiceForCategorie($categorie, 'Service 1');
        
        $categorie->addService($service);
        $this->assertCount(1, $categorie->getServices());
        
        $categorie->removeService($service);
        $this->assertCount(0, $categorie->getServices());
    }
}