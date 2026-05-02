<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PdfService
{
    /**
     * @param string $html
     */
    private function getDompdf(string $html): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true); 

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf;
    }

    /**
     * Pour l'usage Web classique (Téléchargement direct)
     * 
     * @param string $html
     * @param string $filename
     */
    public function showPdfFile(string $html, string $filename): Response
    {
        $dompdf = $this->getDompdf($html);
        $output = $dompdf->output();

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"'
        ]);
    }

    /**
     * Pour l'usage API (Récupère le binaire brut)
     * 
     * @param string $html
     */
    public function getBinaryContent(string $html): string
    {
        $dompdf = $this->getDompdf($html);
        $output = $dompdf->output();

        // On s'assure que la sortie est une chaîne de caractères
        return (string) $output;
    }
}