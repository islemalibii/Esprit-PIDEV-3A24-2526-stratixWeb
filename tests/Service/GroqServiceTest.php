<?php

namespace App\Tests\Service;

use App\Entity\Service;
use App\Entity\CategorieService;
use App\Entity\Utilisateur;
use App\Service\GroqService;
use PHPUnit\Framework\TestCase;

class GroqServiceTest extends TestCase
{
    private function createMockService(float $budget, string $title, ?Utilisateur $user = null): Service
    {
        $service = $this->createMock(Service::class);
        $service->method('getBudget')->willReturn($budget);
        $service->method('getTitre')->willReturn($title);
        $service->method('getDescription')->willReturn('Description');
        $service->method('getUtilisateur')->willReturn($user);
        
        $categorie = $this->createMock(CategorieService::class);
        $categorie->method('getNom')->willReturn('Développement');
        $service->method('getCategorie')->willReturn($categorie);
        
        return $service;
    }
    
    private function createMockUser(string $prenom, string $nom): Utilisateur
    {
        $user = $this->createMock(Utilisateur::class);
        $user->method('getPrenom')->willReturn($prenom);
        $user->method('getNom')->willReturn($nom);
        return $user;
    }
    
    public function testAskQuestionReturnsString(): void
    {
        $apiKey = 'test_key';
        $groqService = new GroqService($apiKey);
        
        $services = [
            $this->createMockService(1000, 'Service 1'),
            $this->createMockService(2000, 'Service 2'),
        ];
        $groqService->setServices($services);
        
        $question = 'Budget total';
        $response = $groqService->ask($question);
        
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }
    
    public function testGetTotalBudgetFromLocalFallback(): void
    {
        $apiKey = 'test_key';
        $groqService = new GroqService($apiKey);
        
        $services = [
            $this->createMockService(1000, 'Service A'),
            $this->createMockService(2000, 'Service B'),
            $this->createMockService(3000, 'Service C'),
        ];
        $groqService->setServices($services);
       
        $question = 'budget total';
        $response = $groqService->ask($question);
        
        $this->assertStringContainsString('6 000', $response);
        $this->assertStringContainsString('Budget total', $response);
    }
    
    public function testGetListOfServices(): void
    {
        $apiKey = 'test_key';
        $groqService = new GroqService($apiKey);
        
        $services = [
            $this->createMockService(1000, 'Formation Java'),
            $this->createMockService(2000, 'Formation PHP'),
        ];
        $groqService->setServices($services);
        
        $response = $groqService->ask('liste des services');
        
        $this->assertStringContainsString('Formation Java', $response);
        $this->assertStringContainsString('Formation PHP', $response);
    }
    
    public function testGetServicesWithoutResponsable(): void
    {
        $apiKey = 'test_key';
        $groqService = new GroqService($apiKey);
        
        $user = $this->createMockUser('John', 'Doe');
        
        $services = [
            $this->createMockService(1000, 'Service A', null), 
            $this->createMockService(2000, 'Service B', $user), 
            $this->createMockService(3000, 'Service C', null), 
        ];
        $groqService->setServices($services);
        
        $response = $groqService->ask('sans responsable');
        
        $this->assertStringContainsString('Service A', $response);
        $this->assertStringContainsString('Service C', $response);
        $this->assertStringNotContainsString('Service B', $response);
    }
    
    public function testGetHighestBudget(): void
    {
        $apiKey = 'test_key';
        $groqService = new GroqService($apiKey);
        
        $services = [
            $this->createMockService(1000, 'Petit budget'),
            $this->createMockService(5000, 'Moyen budget'),
            $this->createMockService(10000, 'Grand budget'),
        ];
        $groqService->setServices($services);
        
        $response = $groqService->ask('plus gros budget');
        
        $this->assertStringContainsString('Grand budget', $response);
        $this->assertStringContainsString('10 000', $response);
    }
    
    public function testSetServicesAcceptsArray(): void
    {
        $apiKey = 'test_key';
        $groqService = new GroqService($apiKey);
        
        $services = [
            $this->createMockService(1000, 'Service 1'),
            $this->createMockService(2000, 'Service 2'),
        ];
        
        $groqService->setServices($services);
        
        $reflection = new \ReflectionClass($groqService);
        $property = $reflection->getProperty('services');
        $property->setAccessible(true);
        $storedServices = $property->getValue($groqService);
        
        $this->assertCount(2, $storedServices);
    }
}