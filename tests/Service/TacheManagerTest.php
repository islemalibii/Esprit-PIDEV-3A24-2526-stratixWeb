<?php
// tests/Service/TacheManagerTest.php

namespace App\Tests\Service;

use App\Entity\Tache;
use App\Service\TacheManager;
use PHPUnit\Framework\TestCase;

class TacheManagerTest extends TestCase
{
    private TacheManager $tacheManager;
    
    protected function setUp(): void
    {
        $this->tacheManager = new TacheManager();
    }
    
    public function testValidTache(): void
    {
        $tache = new Tache();
        $tache->setTitre('Tâche valide');
        $tache->setDescription('Description valide');
        $tache->setDeadline(new \DateTime('+5 days'));
        $tache->setStatut('A_FAIRE');
        $tache->setPriorite('HAUTE');
        $tache->setEmployeId(1);
        
        $result = $this->tacheManager->validate($tache);
        
        $this->assertTrue($result);
    }
    
    public function testTacheWithoutTitre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de la tâche est obligatoire.');
        
        $tache = new Tache();
        $tache->setTitre('');
        $tache->setDescription('Description');
        $tache->setDeadline(new \DateTime('+5 days'));
        $tache->setStatut('A_FAIRE');
        $tache->setPriorite('HAUTE');
        $tache->setEmployeId(1);
        
        $this->tacheManager->validate($tache);
    }
    
    public function testTacheWithTitreTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit contenir au moins 3 caractères.');
        
        $tache = new Tache();
        $tache->setTitre('Ab');
        $tache->setDescription('Description');
        $tache->setDeadline(new \DateTime('+5 days'));
        $tache->setStatut('A_FAIRE');
        $tache->setPriorite('HAUTE');
        $tache->setEmployeId(1);
        
        $this->tacheManager->validate($tache);
    }
    
    public function testTacheWithoutDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description de la tâche est obligatoire.');
        
        $tache = new Tache();
        $tache->setTitre('Titre valide');
        $tache->setDescription('');
        $tache->setDeadline(new \DateTime('+5 days'));
        $tache->setStatut('A_FAIRE');
        $tache->setPriorite('HAUTE');
        $tache->setEmployeId(1);
        
        $this->tacheManager->validate($tache);
    }
    
    public function testTacheWithDeadlineInPast(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La deadline doit être aujourd\'hui ou dans le futur.');
        
        $tache = new Tache();
        $tache->setTitre('Titre valide');
        $tache->setDescription('Description');
        $tache->setDeadline(new \DateTime('-1 day'));
        $tache->setStatut('A_FAIRE');
        $tache->setPriorite('HAUTE');
        $tache->setEmployeId(1);
        
        $this->tacheManager->validate($tache);
    }
    
    public function testTacheIsEnRetard(): void
    {
        $tache = new Tache();
        $tache->setDeadline(new \DateTime('-2 days'));
        $tache->setStatut('A_FAIRE');
        
        $result = $this->tacheManager->isEnRetard($tache);
        
        $this->assertTrue($result);
    }
    
    public function testTerminedTacheIsNotEnRetard(): void
    {
        $tache = new Tache();
        $tache->setDeadline(new \DateTime('-2 days'));
        $tache->setStatut('TERMINEE');
        
        $result = $this->tacheManager->isEnRetard($tache);
        
        $this->assertFalse($result);
    }
    
    public function testGetJoursRetard(): void
    {
        $tache = new Tache();
        $tache->setDeadline(new \DateTime('-4 days'));
        $tache->setStatut('A_FAIRE');
        
        $joursRetard = $this->tacheManager->getJoursRetard($tache);
        
        $this->assertEquals(4, $joursRetard);
    }
    
    public function testTacheUrgente(): void
    {
        $tache = new Tache();
        $tache->setPriorite('HAUTE');
        $tache->setDeadline(new \DateTime('+2 days'));
        
        $result = $this->tacheManager->isUrgente($tache);
        
        $this->assertTrue($result);
    }
    
    public function testMatriceUrgentImportant(): void
    {
        $tache = new Tache();
        $tache->setPriorite('HAUTE');
        $tache->setDeadline(new \DateTime('+2 days'));
        
        $matrice = $this->tacheManager->getMatricePriorite($tache);
        
        $this->assertEquals('URGENT_IMPORTANT', $matrice);
    }
    
    public function testMatriceNonUrgentImportant(): void
    {
        $tache = new Tache();
        $tache->setPriorite('HAUTE');
        $tache->setDeadline(new \DateTime('+20 days'));
        
        $matrice = $this->tacheManager->getMatricePriorite($tache);
        
        $this->assertEquals('NON_URGENT_IMPORTANT', $matrice);
    }
    
    public function testTacheDelegable(): void
    {
        $tache = new Tache();
        $tache->setPriorite('MOYENNE');
        $tache->setDeadline(new \DateTime('+10 days'));
        
        $result = $this->tacheManager->estDelegable($tache);
        
        $this->assertTrue($result);
    }
    
    public function testRecommandationNonUrgente(): void
    {
        $tache = new Tache();
        $tache->setPriorite('HAUTE');
        $tache->setDeadline(new \DateTime('+20 days'));
        
        $recommandation = $this->tacheManager->getRecommandation($tache);
        
        $this->assertStringContainsString('Planifier', $recommandation);
    }
    
    public function testCalculerProgression(): void
    {
        $progression = $this->tacheManager->calculerProgression(7, 10);
        
        $this->assertEquals(70, $progression);
    }
    
    public function testCalculerScoreComplexite(): void
    {
        $tache = new Tache();
        $tache->setTitre('Tâche complexe');
        $tache->setDescription(str_repeat('Description ', 20));
        $tache->setPriorite('HAUTE');
        
        $score = $this->tacheManager->calculerScoreComplexite($tache);
        
        $this->assertGreaterThanOrEqual(50, $score);
        $this->assertLessThanOrEqual(100, $score);
    }
    
    public function testEstimerCharge(): void
    {
        $tache = new Tache();
        $tache->setTitre('Tâche');
        $tache->setDescription('Description');
        $tache->setPriorite('MOYENNE');
        
        $charge = $this->tacheManager->estimerCharge($tache);
        
        $this->assertGreaterThan(0, $charge);
    }
    
    public function testCalculerBurndown(): void
    {
        $taches = [];
        for ($i = 1; $i <= 3; $i++) {
            $tache = new Tache();
            $tache->setTitre("Tâche $i");
            $tache->setDescription("Description $i");
            $tache->setPriorite('MOYENNE');
            $taches[] = $tache;
        }
        
        $burndown = $this->tacheManager->calculerBurndown($taches);
        
        $this->assertArrayHasKey('total_estime', $burndown);
        $this->assertGreaterThan(0, $burndown['total_estime']);
    }
    
    public function testDetecterDependancesCirculaires(): void
    {
        $dependances = [1 => [2], 2 => [3], 3 => [1]];
        
        $result = $this->tacheManager->detecterDependancesCirculaires($dependances);
        
        $this->assertTrue($result);
    }
    
    public function testPasDeDependancesCirculaires(): void
    {
        $dependances = [1 => [2], 2 => [3], 3 => [4]];
        
        $result = $this->tacheManager->detecterDependancesCirculaires($dependances);
        
        $this->assertFalse($result);
    }
}