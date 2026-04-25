<?php

namespace App\Tests\Service;

use App\Entity\Service;
use App\Service\ServiceManager;
use PHPUnit\Framework\TestCase;

class ServiceManagerTest extends TestCase
{
    
    public function testValidService(): void
    {
        $service = new Service();
        $service->setBudget(1000);
        $service->setDateDebut(new \DateTime('2026-04-01'));
        $service->setDateFin(new \DateTime('2026-04-30'));

        $manager = new ServiceManager();

        $this->assertTrue($manager->validate($service));
    }

   
    public function testServiceWithZeroBudget(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le budget doit être supérieur à zéro.');

        $service = new Service();
        $service->setBudget(0);
        $service->setDateDebut(new \DateTime('2026-04-01'));
        $service->setDateFin(new \DateTime('2026-04-30'));

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    
    public function testServiceWithNegativeBudget(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le budget doit être supérieur à zéro.');

        $service = new Service();
        $service->setBudget(-500);
        $service->setDateDebut(new \DateTime('2026-04-01'));
        $service->setDateFin(new \DateTime('2026-04-30'));

        $manager = new ServiceManager();
        $manager->validate($service);
    }

  
    public function testServiceWithNullBudget(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le budget doit être supérieur à zéro.');

        $service = new Service();
        $service->setBudget(null);
        $service->setDateDebut(new \DateTime('2026-04-01'));
        $service->setDateFin(new \DateTime('2026-04-30'));

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    
    public function testServiceWithEndDateBeforeStartDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $service = new Service();
        $service->setBudget(1000);
        $service->setDateDebut(new \DateTime('2026-05-01'));
        $service->setDateFin(new \DateTime('2026-04-30'));

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    
    public function testServiceWithEndDateEqualToStartDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $service = new Service();
        $service->setBudget(1000);
        $service->setDateDebut(new \DateTime('2026-04-15'));
        $service->setDateFin(new \DateTime('2026-04-15'));

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    
    public function testServiceWithOptionalDatesMissing(): void
    {
        $service = new Service();
        $service->setBudget(1000);
        $service->setDateDebut(null);
        $service->setDateFin(null);

        $manager = new ServiceManager();

        $this->assertTrue($manager->validate($service));
    }
}