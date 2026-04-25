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
}