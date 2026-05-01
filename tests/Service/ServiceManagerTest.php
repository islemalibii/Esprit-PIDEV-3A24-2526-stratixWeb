<?php

namespace App\Tests\Service;

use App\Entity\Service;
use App\Entity\CategorieService;
use App\Service\ServiceManager;
use PHPUnit\Framework\TestCase;

class ServiceManagerTest extends TestCase
{
    private function createServiceWithBudget(float $budget): Service
    {
        $service = new Service();
        $service->setBudget($budget);
        $service->setTitre('Service Test');
        return $service;
    }
    
    private function createServiceWithDates(string $start, string $end): Service
    {
        $service = new Service();
        $service->setDateDebut(new \DateTime($start));
        $service->setDateFin(new \DateTime($end));
        $service->setBudget(1000);
        $service->setTitre('Service Test');
        return $service;
    }
    
    private function createServiceWithTitle(string $title): Service
    {
        $service = new Service();
        $service->setTitre($title);
        $service->setBudget(1000);
        return $service;
    }

    public function testValidService(): void
    {
        $service = $this->createServiceWithDates('2026-04-01', '2026-04-30');
        $service->setBudget(1000);
        $manager = new ServiceManager();
        $this->assertTrue($manager->validate($service));
    }

    public function testServiceWithZeroBudget(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le budget doit être supérieur à zéro.');
        $service = $this->createServiceWithDates('2026-04-01', '2026-04-30');
        $service->setBudget(0);
        $manager = new ServiceManager();
        $manager->validate($service);
    }

    public function testServiceWithNegativeBudget(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $service = $this->createServiceWithDates('2026-04-01', '2026-04-30');
        $service->setBudget(-500);
        $manager = new ServiceManager();
        $manager->validate($service);
    }

    public function testServiceWithEndDateAfterStartDate(): void
    {
        $service = $this->createServiceWithDates('2026-04-01', '2026-05-01');
        $service->setBudget(1000);
        $manager = new ServiceManager();
        $this->assertTrue($manager->validate($service));
    }

    public function testServiceWithEndDateBeforeStartDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');
        $service = $this->createServiceWithDates('2026-05-01', '2026-04-01');
        $service->setBudget(1000);
        $manager = new ServiceManager();
        $manager->validate($service);
    }

    public function testCalculateTotalBudget(): void
    {
        $services = [
            $this->createServiceWithBudget(1000),
            $this->createServiceWithBudget(2000),
            $this->createServiceWithBudget(3000),
        ];
        $total = array_sum(array_map(fn($s) => $s->getBudget(), $services));
        $this->assertEquals(6000, $total);
    }

    public function testCalculateAverageBudget(): void
    {
        $services = [
            $this->createServiceWithBudget(1000),
            $this->createServiceWithBudget(2000),
            $this->createServiceWithBudget(3000),
        ];
        $total = array_sum(array_map(fn($s) => $s->getBudget(), $services));
        $average = $total / count($services);
        $this->assertEquals(2000, $average);
    }

    public function testAverageBudgetWithEmptyList(): void
    {
        $services = [];
        $average = count($services) > 0 
            ? array_sum(array_map(fn($s) => $s->getBudget(), $services)) / count($services) 
            : 0;
        $this->assertEquals(0, $average);
    }

    public function testSortServicesByBudgetDescending(): void
    {
        $services = [
            $this->createServiceWithBudget(3000),
            $this->createServiceWithBudget(1000),
            $this->createServiceWithBudget(2000),
        ];
        usort($services, fn($a, $b) => $b->getBudget() <=> $a->getBudget());
        $this->assertEquals(3000, $services[0]->getBudget());
        $this->assertEquals(2000, $services[1]->getBudget());
        $this->assertEquals(1000, $services[2]->getBudget());
    }

    public function testBudgetDistribution(): void
    {
        $services = [
            $this->createServiceWithBudget(1000),
            $this->createServiceWithBudget(5000),
            $this->createServiceWithBudget(2000),
        ];
        $budgets = array_map(fn($s) => $s->getBudget(), $services);
        $min = min($budgets);
        $max = max($budgets);
        $range = $max - $min;
        
        $this->assertEquals(1000, $min);
        $this->assertEquals(5000, $max);
        $this->assertEquals(4000, $range);
    }

    public function testBudgetThresholds(): void
    {
        $lowBudget = $this->createServiceWithBudget(500);
        $mediumBudget = $this->createServiceWithBudget(5000);
        $highBudget = $this->createServiceWithBudget(50000);
        
        $this->assertLessThan(1000, $lowBudget->getBudget());
        $this->assertGreaterThanOrEqual(1000, $mediumBudget->getBudget());
        $this->assertGreaterThan(10000, $highBudget->getBudget());
    }

    public function testSearchServicesByKeyword(): void
    {
        $services = [
            $this->createServiceWithTitle('Formation Java'),
            $this->createServiceWithTitle('Formation PHP'),
            $this->createServiceWithTitle('Maintenance Serveur'),
        ];
        
        $keyword = 'Formation';
        $results = array_filter($services, fn($s) => 
            str_contains($s->getTitre(), $keyword)
        );
        
        $this->assertCount(2, $results);
    }

    public function testServiceCompletionStatus(): void
    {
        $service = $this->createServiceWithDates('2025-01-01', '2025-01-31');
        $service->setArchive(false);
        
        $isCompleted = $service->getDateFin() < new \DateTime();
        
        $this->assertTrue($isCompleted);
    }

    public function testDateRangeValidation(): void
    {
        $start = new \DateTime('2026-04-01');
        $end = new \DateTime('2026-04-30');
        $daysDiff = $start->diff($end)->days;
        
        $this->assertEquals(29, $daysDiff);
    }
    public function testFindMostExpensiveService(): void
{
    $services = [
        $this->createServiceWithBudget(1000),
        $this->createServiceWithBudget(5000),
        $this->createServiceWithBudget(3000),
    ];
    
    $mostExpensive = $services[0];
    foreach ($services as $service) {
        if ($service->getBudget() > $mostExpensive->getBudget()) {
            $mostExpensive = $service;
        }
    }
    
    $this->assertEquals(5000, $mostExpensive->getBudget());
}
}