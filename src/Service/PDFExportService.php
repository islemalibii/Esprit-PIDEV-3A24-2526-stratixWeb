<?php

namespace App\Service;

use App\Entity\Service;
use TCPDF;

class PDFExportService
{
    public function exportServicesToPDF(array $services, string $title = "Liste des Services"): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
       
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', '', 12);
        
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Exporté le: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
        $pdf->Ln(10);
        
        $totalBudget = array_sum(array_map(fn($s) => $s->getBudget(), $services));
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 8, ' Informations du Services ', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, '• Nombre total de services: ' . count($services), 0, 1, 'L');
        $pdf->Cell(0, 7, '• Budget total: ' . number_format($totalBudget, 0, ',', ' ') . ' DT', 0, 1, 'L');
        $pdf->Cell(0, 7, '• Budget moyen: ' . number_format($totalBudget / count($services), 0, ',', ' ') . ' DT', 0, 1, 'L');
        $pdf->Ln(10);
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(52, 152, 219);
        $pdf->SetTextColor(255, 255, 255);
        
        $pdf->Cell(10, 10, 'N°', 1, 0, 'C', true);
        $pdf->Cell(80, 10, 'Titre du Service', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Budget (DT)', 1, 0, 'C', true);
        $pdf->Cell(50, 10, 'Catégorie', 1, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(245, 245, 245);
        
        $fill = false;
        foreach ($services as $index => $service) {
            $pdf->Cell(10, 8, $index + 1, 1, 0, 'C', $fill);
            $pdf->Cell(80, 8, $service->getTitre(), 1, 0, 'L', $fill);
            $pdf->Cell(40, 8, number_format($service->getBudget(), 0, ',', ' '), 1, 0, 'R', $fill);
            $categorie = $service->getCategorie() ? $service->getCategorie()->getNom() : 'Non catégorisé';
            $pdf->Cell(50, 8, $categorie, 1, 1, 'L', $fill);
            $fill = !$fill;
        }
        
        $pdf->Ln(10);
        
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(128, 128, 128);
      
        
        return $pdf->Output('', 'S');
    }
}