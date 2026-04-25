<?php

namespace App\Controller;

// --- CES IMPORTS SONT OBLIGATOIRES ---
use App\Entity\Ressource;
use App\Repository\OffreRepository;
use App\Service\DecisionMatrixService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ComparateurController extends AbstractController
{
    #[Route('/admin/comparateur/{id}', name: 'app_comparateur')]
    public function compare(
        Ressource $ressource, 
        OffreRepository $offreRepo, 
        DecisionMatrixService $ai
    ): Response
    {
        $offres = $offreRepo->findBy(['ressource' => $ressource]);
        $analyse = $ai->getRecommendation($offres);

        return $this->render('comparateur/index.html.twig', [
            'ressource' => $ressource,
            'analyse' => $analyse
        ]);
    }
}