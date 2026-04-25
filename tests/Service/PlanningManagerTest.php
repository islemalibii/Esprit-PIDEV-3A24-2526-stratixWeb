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
    
    // Test 1: Planning valide
    public function testValidPlanning(): void
    {
        $planning = new Planning();
        $planning->setDate(new \DateTime('+2 days'));
        $planning->setTypeShift('MATIN');
        $planning->setEmployeId(1);
        
        $result = $this->planningManager->validate($planning);
        
        $this->assertTrue($result);
    }
    
    // Test 2: Planning sans date
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
    
    // Test 3: Planning date passée
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
    
    // Test 4: Planning sans type shift
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
    
    // Test 5: Planning type shift invalide
    public function testPlanningWithInvalidTypeShift(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $planning = new Planning();
        $planning->setDate(new \DateTime('+2 days'));
        $planning->setTypeShift('INVALIDE');
        $planning->setEmployeId(1);
        
        $this->planningManager->validate($planning);
    }
    
    // Test 6: Planning sans employé
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
}