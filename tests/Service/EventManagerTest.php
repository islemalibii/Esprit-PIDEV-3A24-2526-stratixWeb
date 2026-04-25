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

    public function testEvenementTitreTropCourt()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit faire au moins 5 caracteres');

        $evenement = $this->createValidEvenement();
        $evenement->setTitre('abc');

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementTitreTropLong()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre ne peut pas dépasser 100 caracteres');

        $evenement = $this->createValidEvenement();
        $evenement->setTitre(str_repeat('a', 101)); 

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

    public function testEvenementDescriptionTropCourte()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est trop courte');

        $evenement = $this->createValidEvenement();
        $evenement->setDescription('court');

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

    public function testEvenementDateMoinsDe3Jours()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'evenement doit etre planifie au moins 3 jours a l'avance");

        $evenement = $this->createValidEvenement();
        $evenement->setDateEvent(new \DateTime('+1 day'));

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementSansLieu()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le lieu est obligatoire');

        $evenement = $this->createValidEvenement();
        $evenement->setLieu('');

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementSansType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le type d'événement est obligatoire");

        $evenement = $this->createValidEvenement();
        $evenement->setTypeEvent('');

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementTypeInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Type d'événement invalide");

        $evenement = $this->createValidEvenement();
        $evenement->setTypeEvent('typeInvalide');

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementStatutInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Statut invalide');

        $evenement = $this->createValidEvenement();
        $evenement->setStatut('statutInvalide');

        $manager = new EventManage();
        $manager->validate($evenement);
    }

    public function testEvenementTousLesTypesValides()
    {
        $manager    = new EventManage();
        $validTypes = ['reunion', 'formation', 'lancementProduit', 'seminaire', 'recrutement'];

        foreach ($validTypes as $type) {
            $evenement = $this->createValidEvenement();
            $evenement->setTypeEvent($type);
            $this->assertTrue($manager->validate($evenement));
        }
    }

    public function testEvenementTousLesStatutsValides()
    {
        $manager       = new EventManage();
        $validStatuts  = ['planifier', 'annuler', 'terminer'];

        foreach ($validStatuts as $statut) {
            $evenement = $this->createValidEvenement();
            $evenement->setStatut($statut);
            $this->assertTrue($manager->validate($evenement));
        }
    }
}