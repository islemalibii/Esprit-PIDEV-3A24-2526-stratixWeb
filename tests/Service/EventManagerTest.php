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

    

    public function testEvenementAnnuleNonParticipable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Un événement annulé ne peut pas accepter de participations");

        $evenement = $this->createValidEvenement();
        $evenement->setStatut('annuler');
        $manager = new EventManage();
        $manager->validateParticipation($evenement);
    }

    public function testEvenementTermineNonParticipable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Un événement terminé ne peut pas accepter de participations");

        $evenement = $this->createValidEvenement();
        $evenement->setStatut('terminer');
        $manager = new EventManage();
        $manager->validateParticipation($evenement);
    }

    public function testEvenementPlanifieAccepteParticipation()
    {
        $evenement = $this->createValidEvenement();
        $evenement->setStatut('planifier');

        $manager = new EventManage();
        $this->assertTrue($manager->validateParticipation($evenement));
    }

    public function testRecurrenceValide()
    {
        $manager          = new EventManage();
        $validRecurrences = ['none', 'weekly', 'monthly'];

        foreach ($validRecurrences as $recurrence) {
            $evenement = $this->createValidEvenement();
            $evenement->setRecurrence($recurrence);
            $this->assertTrue($manager->validateRecurrence($evenement));
        }
    }

    public function testRecurrenceInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Récurrence invalide");

        $evenement = $this->createValidEvenement();
        $evenement->setRecurrence('daily');

        $manager = new EventManage();
        $manager->validateRecurrence($evenement);
    }

    public function testTousLesFormatsImageValides()
    {
        $manager         = new EventManage();
        $validExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    
        foreach ($validExtensions as $ext) {
            $evenement = $this->createValidEvenement();
            $evenement->setImageUrl('/uploads/events/photo.' . $ext);
            $this->assertTrue($manager->validateImageUrl($evenement));
        }
    }

    public function testImageUrlFormatInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Format d'image invalide. Utilisez JPG, PNG ou WEBP");

        $evenement = $this->createValidEvenement();
        $evenement->setImageUrl('/uploads/events/document.pdf');

        $manager = new EventManage();
        $manager->validateImageUrl($evenement);
    }

}