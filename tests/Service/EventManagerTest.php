<?php
// tests/Service/EvenementManagerTest.php

namespace App\Tests\Service;

use App\Entity\Evenement;
use App\Service\EventManage;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    private function createValidEvenement(): Evenement
    {
        $evenement = new Evenement();
        $evenement->setTitre('Formation js');
        $evenement->setDescription('Formation sur js pour les developpeurs web');
        $evenement->setDateEvent(new \DateTime('+7 days'));
        $evenement->setLieu('Esprit, Tunis');
        $evenement->setTypeEvent('formation');
        $evenement->setStatut('planifier');
        return $evenement;
    }

    public function testValidEvenement()
    {
        $evenement = $this->createValidEvenement();
        $manager   = new EventManage();

        $this->assertTrue($manager->validate($evenement));
    }

    public function testEvenementSansTitre()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');

        $evenement = $this->createValidEvenement();
        $evenement->setTitre('');

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    

   

    public function testEvenementSansDescription()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire');

        $evenement = $this->createValidEvenement();
        $evenement->setDescription('');

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    

    public function testEvenementSansDate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date est obligatoire');

        $evenement = $this->createValidEvenement();
        $evenement->setDateEvent(null);

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementDatePassee()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La date de l'événement ne peut pas être dans le passé");

        $evenement = $this->createValidEvenement();
        $evenement->setDateEvent(new \DateTime('-1 day'));

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    
    
}