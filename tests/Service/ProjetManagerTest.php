<?php

namespace App\Tests\Service;

use App\Entity\Projet;
use App\Service\ProjetManager;
use PHPUnit\Framework\TestCase;

class ProjetManagerTest extends TestCase
{
    // Scénario de succès : Projet valide [cite: 70]
    public function testValidProjet(): void
    {
        $projet = new Projet();
        $projet->setNom('Application Stratix');
        $projet->setDateDebut(new \DateTime('2026-01-01'));
        $projet->setDateFin(new \DateTime('2026-12-31'));

        $manager = new ProjetManager();
        $this->assertTrue($manager->validate($projet)); // [cite: 78]
    }

    // Test Règle 1 : Nom vide [cite: 79]
    public function testProjetWithoutNom(): void
    {
        $this->expectException(\InvalidArgumentException::class); // [cite: 82]
        
        $projet = new Projet();
        $projet->setNom(''); // Nom vide
        $projet->setDateDebut(new \DateTime('2026-01-01'));
        $projet->setDateFin(new \DateTime('2026-12-31'));

        $manager = new ProjetManager();
        $manager->validate($projet);
    }

    // Test Règle 2 : Dates invalides [cite: 87]
    public function testProjetWithInvalidDates(): void
    {
        $this->expectException(\InvalidArgumentException::class); // [cite: 89]
        
        $projet = new Projet();
        $projet->setNom('Projet Test');
        $projet->setDateDebut(new \DateTime('2026-05-01'));
        $projet->setDateFin(new \DateTime('2026-04-01')); // Date de fin AVANT début

        $manager = new ProjetManager();
        $manager->validate($projet);
    }
}