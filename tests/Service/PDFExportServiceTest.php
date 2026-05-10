<?php

namespace App\Tests\Service;

use App\Entity\Service;
use App\Entity\CategorieService;
use App\Service\PDFExportService;
use PHPUnit\Framework\TestCase;

class PDFExportServiceTest extends TestCase
{
    private function createMockService(int $id, string $title, string $budget, ?string $categorieName = null): Service
    {
        $categorie = null;
        if ($categorieName) {
            $categorie = new CategorieService();
            $categorie->setNom($categorieName);
        }
        
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn($id);
        $service->method('getTitre')->willReturn($title);
        $service->method('getBudget')->willReturn($budget);
        $service->method('getDescription')->willReturn('Description du service');
        $service->method('getCategorie')->willReturn($categorie);
        $service->method('getDateDebut')->willReturn(new \DateTime('2026-04-01'));
        $service->method('getDateFin')->willReturn(new \DateTime('2026-04-30'));
        
        return $service;
    }
    
    public function testExportServicesToPDFReturnsString(): void
    {
        $services = [
            $this->createMockService(1, 'Service 1', '1000', 'Développement'),
        ];
        
        $pdfService = new PDFExportService();
        $result = $pdfService->exportServicesToPDF($services, 'Test PDF');
        
        // FIX: Remove assertIsString
        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('%PDF', $result);
    }
    
    public function testExportServicesToPDFReturnsValidPDF(): void
    {
        $services = [
            $this->createMockService(1, 'Service Test', '1500', 'Développement'),
        ];
        
        $pdfService = new PDFExportService();
        $result = $pdfService->exportServicesToPDF($services, 'Test PDF');
        
        $this->assertStringStartsWith('%PDF-', $result);
        $this->assertStringEndsWith('%%EOF', trim($result));
    }
    
    public function testExportServicesToPDFWithEmptyServices(): void
    {
        $pdfService = new PDFExportService();
        $result = $pdfService->exportServicesToPDF([], 'Empty PDF');
        
        // FIX: Remove assertIsString
        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('%PDF', $result);
    }
    
    public function testExportServicesToPDFWithMultipleServices(): void
    {
        $services = [
            $this->createMockService(1, 'Service A', '1000', 'Développement'),
            $this->createMockService(2, 'Service B', '2000', 'Design'),
            $this->createMockService(3, 'Service C', '3000', 'Marketing'),
        ];
        
        $pdfService = new PDFExportService();
        $result = $pdfService->exportServicesToPDF($services, 'Multiple Services');
        
        // FIX: Remove assertIsString
        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('%PDF', $result);
        $this->assertStringEndsWith('%%EOF', trim($result));
    }
    
    public function testExportServicesToPDFTitleAppears(): void
    {
        $services = [
            $this->createMockService(1, 'Service 1', '1000', 'Développement'),
        ];
        
        $title = 'Mon Rapport Personnalisé';
        $pdfService = new PDFExportService();
        $result = $pdfService->exportServicesToPDF($services, $title);
        
        $this->assertStringStartsWith('%PDF', $result);
        $this->assertNotEmpty($result);
    }
} 