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

    public function testValidEvenement(): void
    {
        $evenement = $this->createValidEvenement();
        $manager   = new EventManage();
        $this->assertTrue($manager->validate($evenement));
    }

    public function testEvenementSansTitre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');
        $evenement = $this->createValidEvenement();
        $evenement->setTitre('');
        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementSansDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire');
        $evenement = $this->createValidEvenement();
        $evenement->setDescription('');
        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementSansDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date est obligatoire');
        $evenement = $this->createValidEvenement();
        $evenement->setDateEvent(null);
        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementDatePassee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La date de l'événement ne peut pas être dans le passé");
        $evenement = $this->createValidEvenement();
        $evenement->setDateEvent(new \DateTime('-1 day'));
        $manager = new EventManage();
        $manager->validate($evenement);
    }

    
    public function testEvenementPlanifieAccepteParticipation(): void
    {
        $evenement = $this->createValidEvenement();
        $evenement->setStatut('planifier');
        $manager = new EventManage();
        $this->assertTrue($manager->validateParticipation($evenement));
    }

    public function testEvenementAnnuleNonParticipable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Un événement annulé ne peut pas accepter de participations");
        $evenement = $this->createValidEvenement();
        $evenement->setStatut('annuler');
        $manager = new EventManage();
        $manager->validateParticipation($evenement);
    }

    public function testEvenementTermineNonParticipable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Un événement terminé ne peut pas accepter de participations");
        $evenement = $this->createValidEvenement();
        $evenement->setStatut('terminer');
        $manager = new EventManage();
        $manager->validateParticipation($evenement);
    }



    public function testRecurrenceValide(): void
    {
        $manager = new EventManage();
        $validRecurrences = ['none', 'weekly', 'monthly'];
        foreach ($validRecurrences as $recurrence) {
            $evenement = $this->createValidEvenement();
            $evenement->setRecurrence($recurrence);
            $this->assertTrue($manager->validateRecurrence($evenement));
        }
    }

    public function testRecurrenceInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Récurrence invalide");
        $evenement = $this->createValidEvenement();
        $evenement->setRecurrence('daily');
        $manager = new EventManage();
        $manager->validateRecurrence($evenement);
    }

    public function testTousLesFormatsImageValides(): void
    {
        $manager = new EventManage();
        $validExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($validExtensions as $ext) {
            $evenement = $this->createValidEvenement();
            $evenement->setImageUrl('/uploads/events/photo.' . $ext);
            $this->assertTrue($manager->validateImageUrl($evenement));
        }
    }

    public function testImageUrlFormatInvalide() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Format d'image invalide. Utilisez JPG, PNG ou WEBP");

        $evenement = $this->createValidEvenement();
        $evenement->setImageUrl('/uploads/events/document.pdf');
        $manager = new EventManage();
        $manager->validateImageUrl($evenement);
    }



    public function testTousLesRatingsValides()
    {
        $manager = new EventManage();
        foreach ([1, 2, 3, 4, 5] as $rating) {
            $this->assertTrue($manager->validateRating($rating));
        }
    }

    public function testRatingTropEleve()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La note doit être entre 1 et 5");
        $manager = new EventManage();
        $manager->validateRating(6);
    }

    public function testRatingTropBas()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La note doit être entre 1 et 5");
        $manager = new EventManage();
        $manager->validateRating(0);
    }


    public function testCommentaireNullValide()
    {
        $manager = new EventManage();
        $this->assertTrue($manager->validateCommentaire(null));
    }

    public function testCommentaireValide()
    {
        $manager = new EventManage();
        $this->assertTrue($manager->validateCommentaire('Très bon événement, bien organisé.'));
    }

    public function testCommentaireTropLong()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le commentaire ne peut pas dépasser 500 caractères");

        $manager = new EventManage();
        $manager->validateCommentaire(str_repeat('a', 501));
    }

}