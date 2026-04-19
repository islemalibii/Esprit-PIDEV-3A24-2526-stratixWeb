<?php

namespace App\Controller;

use App\Service\AiResumeService;
use Smalot\PdfParser\Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResumeController extends AbstractController
{
    #[Route('/resume', name: 'resume')]
    public function resume(Request $request, AiResumeService $aiService): Response
    {
        $summary = null;

        if ($request->isMethod('POST')) {
            $file = $request->files->get('document');

            if ($file) {
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getPathname());

                $text = $pdf->getText();

                $summary = $aiService->generateSummary($text);
            }
        }

        return $this->render('resume/index.html.twig', [
            'summary' => $summary
        ]);
    }
}