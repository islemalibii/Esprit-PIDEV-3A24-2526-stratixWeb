<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PdfService
{
    private function getDompdf($html): Dompdf
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
     */
    public function showPdfFile($html, $filename): Response
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
     */
    public function getBinaryContent($html): string
    {
        $dompdf = $this->getDompdf($html);
        return $dompdf->output();
    }
}