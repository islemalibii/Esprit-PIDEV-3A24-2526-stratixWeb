<?php

namespace App\Tests\Service;

use App\Entity\Projet;
use App\Entity\Phase;
use App\Service\ProjetManager;
use PHPUnit\Framework\TestCase;

class ProjetManagerTest extends TestCase
{
    private ProjetManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ProjetManager();
    }

    /**
     * 1. Teste qu'un projet avec des données valides est accepté
     */
    public function testValidProjet(): void
    {
        $projet = new Projet();
        $projet->setNom('Application Stratix');
        $projet->setDateDebut(new \DateTime('2026-01-01'));
        $projet->setDateFin(new \DateTime('2026-12-31'));

        $this->assertTrue($this->manager->validate($projet)); 
    }

    /**
     * 2. Teste qu'un nom vide déclenche une exception
     */
    public function testProjetWithoutNom(): void
    {
        $this->expectException(\InvalidArgumentException::class); 
        
        $projet = new Projet();
        $projet->setNom(''); 
        $projet->setDateDebut(new \DateTime('2026-01-01'));
        $projet->setDateFin(new \DateTime('2026-12-31'));

        $this->manager->validate($projet);
    }

    /**
     * 3. Teste que la date de fin ne peut pas être avant la date de début
     */
    public function testProjetWithInvalidDates(): void
    {
        $this->expectException(\InvalidArgumentException::class); 
        
        $projet = new Projet();
        $projet->setNom('Projet Test');
        $projet->setDateDebut(new \DateTime('2026-05-01'));
        $projet->setDateFin(new \DateTime('2026-04-01')); 

        $this->manager->validate($projet);
    }

    /**
     * 4. Teste qu'une phase ne peut pas dépasser la fin du projet
     */
    public function testPhaseEndsAfterProjet(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $projet = new Projet();
        $projet->setDateDebut(new \DateTime('2026-05-01'));
        $projet->setDateFin(new \DateTime('2026-05-31'));

        $phase = new Phase();
        $phase->setNom('Phase de test');
        $phase->setDateDebut(new \DateTime('2026-05-10'));
        $phase->setDateFin(new \DateTime('2026-06-05')); // Invalide

        $projet->addPhase($phase);

        $this->manager->validate($projet);
    }

    /**
     * 5. Teste qu'une phase ne peut pas commencer avant le début du projet
     */
    public function testPhaseStartsBeforeProjet(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $projet = new Projet();
        $projet->setDateDebut(new \DateTime('2026-05-01'));
        $projet->setDateFin(new \DateTime('2026-05-31'));

        $phase = new Phase();
        $phase->setDateDebut(new \DateTime('2026-04-20')); // Invalide
        $phase->setDateFin(new \DateTime('2026-05-15'));

        $projet->addPhase($phase);

        $this->manager->validate($projet);
    }

    /**
     * 6. TEST INNOVANT : Teste la détection de chevauchement entre deux phases
     */
    public function testPhaseOverlapConflict(): void
    {
        $this->expectException(\LogicException::class);

        $projet = new Projet();
        $projet->setNom('Chantier Stratix');
        $projet->setDateDebut(new \DateTime('2026-01-01'));
        $projet->setDateFin(new \DateTime('2026-12-31'));

        // Phase 1 : du 1er au 15 Janvier
        $p1 = new Phase();
        $p1->setNom('Analyse');
        $p1->setDateDebut(new \DateTime('2026-01-01'));
        $p1->setDateFin(new \DateTime('2026-01-15'));
        $projet->addPhase($p1);

        // Phase 2 : du 10 au 20 Janvier (Conflit avec Phase 1)
        $p2 = new Phase();
        $p2->setNom('Design');
        $p2->setDateDebut(new \DateTime('2026-01-10')); 
        $p2->setDateFin(new \DateTime('2026-01-20'));
        $projet->addPhase($p2);

        $this->manager->validate($projet);
    }
    /**
     *Un projet ne peut pas être fini s'il reste des phases actives
     */
    public function testProjetCannotBeFinishedWithActivePhases(): void {
        $this->expectException(\LogicException::class);

        $projet = new Projet();
        $projet->setNom('Projet Stratix');
        $projet->setStatut('Terminée'); // On veut finir le projet...
        $projet->setDateDebut(new \DateTime('2026-01-01'));
        $projet->setDateFin(new \DateTime('2026-12-31'));

        $phaseOuverte = new Phase();
        $phaseOuverte->setNom('Développement');
        $phaseOuverte->setStatut('En cours'); 
        $phaseOuverte->setDateDebut(new \DateTime('2026-01-01'));
        $phaseOuverte->setDateFin(new \DateTime('2026-06-01'));

        $projet->addPhase($phaseOuverte);

        $this->manager->validate($projet);
    }
}