<?php
// tests/Service/PlanningManagerTest.php

namespace App\Tests\Service;

use App\Entity\Planning;
use App\Service\PlanningManager;
use PHPUnit\Framework\TestCase;

class PlanningManagerTest extends TestCase
{
    private PlanningManager $planningManager;
    
    protected function setUp(): void
    {
        $this->planningManager = new PlanningManager();
    }
    
    // ========== TESTS DE VALIDATION (déjà existants) ==========
    
    public function testValidPlanning(): void
    {
        $planning = new Planning();
        $planning->setDate(new \DateTime('+2 days'));
        $planning->setTypeShift('MATIN');
        $planning->setEmployeId(1);
        
        $result = $this->planningManager->validate($planning);
        
        $this->assertTrue($result);
    }
    
    public function testPlanningWithoutDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date du planning est obligatoire.');
        
        $planning = new Planning();
        $planning->setDate(null);
        $planning->setTypeShift('MATIN');
        $planning->setEmployeId(1);
        
        $this->planningManager->validate($planning);
    }
    
    public function testPlanningWithPastDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date doit être aujourd\'hui ou dans le futur.');
        
        $planning = new Planning();
        $planning->setDate(new \DateTime('-1 day'));
        $planning->setTypeShift('MATIN');
        $planning->setEmployeId(1);
        
        $this->planningManager->validate($planning);
    }
    
    public function testPlanningWithoutTypeShift(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type de shift est obligatoire.');
        
        $planning = new Planning();
        $planning->setDate(new \DateTime('+2 days'));
        $planning->setTypeShift('');
        $planning->setEmployeId(1);
        
        $this->planningManager->validate($planning);
    }
    
    public function testPlanningWithInvalidTypeShift(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $planning = new Planning();
        $planning->setDate(new \DateTime('+2 days'));
        $planning->setTypeShift('INVALIDE');
        $planning->setEmployeId(1);
        
        $this->planningManager->validate($planning);
    }
    
    public function testPlanningWithoutEmploye(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'employé assigné est obligatoire.');
        
        $planning = new Planning();
        $planning->setDate(new \DateTime('+2 days'));
        $planning->setTypeShift('MATIN');
        $planning->setEmployeId(null);
        
        $this->planningManager->validate($planning);
    }
    
    // ========== NOUVEAUX TESTS MÉTIERS AVANCÉS ==========
    
    /**
     * Test métier 1: Vérifier si un shift est de nuit
     */
    public function testShiftDeNuit(): void
    {
        $planning = new Planning();
        $planning->setHeureDebut(new \DateTime('22:00'));
        $planning->setHeureFin(new \DateTime('06:00'));
        
        $result = $this->planningManager->isShiftDeNuit($planning);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test métier 2: Vérifier si un shift n'est PAS de nuit
     */
    public function testShiftMatinPasNuit(): void
    {
        $planning = new Planning();
        $planning->setHeureDebut(new \DateTime('08:00'));
        $planning->setHeureFin(new \DateTime('12:00'));
        
        $result = $this->planningManager->isShiftDeNuit($planning);
        
        $this->assertFalse($result);
    }
    
    /**
     * Test métier 3: Calcul des heures supplémentaires
     */
    public function testHeuresSupplementaires(): void
    {
        $planning = new Planning();
        $planning->setHeureDebut(new \DateTime('08:00'));
        $planning->setHeureFin(new \DateTime('18:00'));
        $planning->setTypeShift('MATIN');
        
        $heuresSupp = $this->planningManager->getHeuresSupplementaires($planning);
        
        $this->assertEquals(3, $heuresSupp); // 10h - 7h = 3h supp
    }
    
    /**
     * Test métier 4: Pas d'heures supplémentaires pour RTT
     */
    public function testRttPasHeuresSupplementaires(): void
    {
        $planning = new Planning();
        $planning->setHeureDebut(new \DateTime('08:00'));
        $planning->setHeureFin(new \DateTime('18:00'));
        $planning->setTypeShift('RTT');
        
        $heuresSupp = $this->planningManager->getHeuresSupplementaires($planning);
        
        $this->assertEquals(0, $heuresSupp);
    }
    
    /**
     * Test métier 5: Détection de conflit entre plannings
     */
    public function testPlanningsEnConflit(): void
    {
        $planning1 = new Planning();
        $planning1->setDate(new \DateTime('2026-05-15'));
        $planning1->setEmployeId(1);
        
        $planning2 = new Planning();
        $planning2->setDate(new \DateTime('2026-05-15'));
        $planning2->setEmployeId(1);
        
        $conflit = $this->planningManager->estEnConflit($planning1, $planning2);
        
        $this->assertTrue($conflit);
    }
    
    /**
     * Test métier 6: Pas de conflit entre différents employés
     */
    public function testPasConflitEmployesDifferents(): void
    {
        $planning1 = new Planning();
        $planning1->setDate(new \DateTime('2026-05-15'));
        $planning1->setEmployeId(1);
        
        $planning2 = new Planning();
        $planning2->setDate(new \DateTime('2026-05-15'));
        $planning2->setEmployeId(2);
        
        $conflit = $this->planningManager->estEnConflit($planning1, $planning2);
        
        $this->assertFalse($conflit);
    }
    
    /**
     * Test métier 7: Calcul du temps de repos entre deux shifts
     */
    public function testTempsReposEntreShifts(): void
    {
        $planning1 = new Planning();
        $planning1->setDate(new \DateTime('2026-05-15'));
        $planning1->setHeureFin(new \DateTime('18:00'));
        
        $planning2 = new Planning();
        $planning2->setDate(new \DateTime('2026-05-16'));
        $planning2->setHeureDebut(new \DateTime('08:00'));
        
        $repos = $this->planningManager->getTempsRepos($planning1, $planning2);
        
        $this->assertEquals(14, $repos); // 18h au 8h = 14h
    }
    
    /**
     * Test métier 8: Congés consécutifs
     */
    public function testCongesConsecutifs(): void
    {
        $planning1 = new Planning();
        $planning1->setDate(new \DateTime('2026-05-15'));
        $planning1->setTypeShift('CONGE');
        
        $planning2 = new Planning();
        $planning2->setDate(new \DateTime('2026-05-16'));
        $planning2->setTypeShift('CONGE');
        
        $result = $this->planningManager->sontCongesConsecutifs($planning1, $planning2);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test métier 9: Non consécutifs si dates espacées
     */
    public function testCongesNonConsecutifs(): void
    {
        $planning1 = new Planning();
        $planning1->setDate(new \DateTime('2026-05-15'));
        $planning1->setTypeShift('CONGE');
        
        $planning2 = new Planning();
        $planning2->setDate(new \DateTime('2026-05-20'));
        $planning2->setTypeShift('CONGE');
        
        $result = $this->planningManager->sontCongesConsecutifs($planning1, $planning2);
        
        $this->assertFalse($result);
    }
    
    /**
     * Test métier 10: Vérification conformité légale (durée max 10h)
     */
    public function testConformiteLegaleOk(): void
    {
        $planning = new Planning();
        $planning->setHeureDebut(new \DateTime('08:00'));
        $planning->setHeureFin(new \DateTime('17:00')); // 9h
        
        $result = $this->planningManager->verifierConformiteLegale($planning);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test métier 11: Non conforme si durée > 10h
     */
    public function testConformiteLegaleNonConforme(): void
    {
        $planning = new Planning();
        $planning->setHeureDebut(new \DateTime('08:00'));
        $planning->setHeureFin(new \DateTime('21:00')); // 13h
        
        $result = $this->planningManager->verifierConformiteLegale($planning);
        
        $this->assertFalse($result);
    }
    
    /**
     * Test métier 12: Détection des trous dans le planning
     */
    public function testDetecterTrous(): void
    {
        $planning1 = new Planning();
        $planning1->setDate(new \DateTime('2026-05-01'));
        
        $planning2 = new Planning();
        $planning2->setDate(new \DateTime('2026-05-03')); // Trou le 2 mai
        
        $plannings = [$planning1, $planning2];
        
        $trous = $this->planningManager->detecterTrous($plannings);
        
        $this->assertCount(1, $trous);
        $this->assertEquals('2026-05-02', $trous[0]['debut']);
        $this->assertEquals('2026-05-02', $trous[0]['fin']);
    }
    
    /**
     * Test métier 13: Aucun trou si dates continues
     */
    public function testAucunTrou(): void
    {
        $planning1 = new Planning();
        $planning1->setDate(new \DateTime('2026-05-01'));
        
        $planning2 = new Planning();
        $planning2->setDate(new \DateTime('2026-05-02'));
        
        $plannings = [$planning1, $planning2];
        
        $trous = $this->planningManager->detecterTrous($plannings);
        
        $this->assertCount(0, $trous);
    }
    
    /**
     * Test métier 14: Optimisation de la répartition
     */
    public function testOptimiserRepartition(): void
    {
        $planning1 = new Planning();
        $planning1->setEmployeId(1);
        
        $planning2 = new Planning();
        $planning2->setEmployeId(1);
        
        $planning3 = new Planning();
        $planning3->setEmployeId(2);
        
        $plannings = [$planning1, $planning2, $planning3];
        
        $repartition = $this->planningManager->optimiserRepartition($plannings);
        
        $this->assertEquals(3, $repartition['total_shifts']);
        $this->assertEquals(2, $repartition['employes_concernes']);
    }
    
    /**
     * Test métier 15: Rotation équitable des shifts
     */
    public function testGenererRotation(): void
    {
        $employes = [1, 2, 3];
        $shifts = ['MATIN', 'SOIR', 'NUIT'];
        $nbJours = 9;
        
        $rotation = $this->planningManager->genererRotation($employes, $shifts, $nbJours);
        
        $this->assertCount($nbJours, $rotation);
        $this->assertEquals(1, $rotation[0]['employe']);
        $this->assertEquals(2, $rotation[1]['employe']);
        $this->assertEquals(3, $rotation[2]['employe']);
        $this->assertEquals('MATIN', $rotation[0]['shift']);
        $this->assertEquals('SOIR', $rotation[1]['shift']);
        $this->assertEquals('NUIT', $rotation[2]['shift']);
    }
    
    /**
     * Test métier 16: Prédiction des prochaines absences
     */
    public function testPredictionAbsences(): void
    {
        $historique = [
            ['employe' => 1, 'date' => '2026-02-15', 'type' => 'CONGE'],
            ['employe' => 1, 'date' => '2026-03-10', 'type' => 'CONGE'],
            ['employe' => 1, 'date' => '2026-04-05', 'type' => 'MALADIE'],
        ];
        
        $prediction = $this->planningManager->predireProchaineAbsence($historique, 1);
        
        $this->assertArrayHasKey('probabilite', $prediction);
        $this->assertArrayHasKey('prochaine_prevue', $prediction);
        $this->assertGreaterThan(0, $prediction['probabilite']);
    }
}