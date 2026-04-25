<?php

namespace App\Controller;

use App\Repository\TacheRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticsController extends AbstractController
{
    #[Route('/statistics', name: 'app_statistics_index')]
    public function index(TacheRepository $tacheRepository): Response
    {
        $taches = $tacheRepository->findAll();
        
        $total = count($taches);
        $aFaire = 0;
        $enCours = 0;
        $terminees = 0;
        $haute = 0;
        $moyenne = 0;
        $basse = 0;
        $enRetard = 0;
        $today = new \DateTime();
        
        foreach ($taches as $t) {
            if ($t->getStatut() === 'A_FAIRE') $aFaire++;
            if ($t->getStatut() === 'EN_COURS') $enCours++;
            if ($t->getStatut() === 'TERMINEE') $terminees++;
            if ($t->getPriorite() === 'HAUTE') $haute++;
            if ($t->getPriorite() === 'MOYENNE') $moyenne++;
            if ($t->getPriorite() === 'BASSE') $basse++;
            
            if ($t->getDeadline() && $t->getDeadline() < $today && $t->getStatut() !== 'TERMINEE') {
                $enRetard++;
            }
        }
        
        $tauxCompletion = $total > 0 ? round(($terminees / $total) * 100, 2) : 0;
        
        // CORRECTION ICI : utilise 'statistics/index.html.twig' au lieu de 'admin/statistics/index.html.twig'
        return $this->render('admin/statistics/index.html.twig', [
            'total' => $total,
            'a_faire' => $aFaire,
            'en_cours' => $enCours,
            'terminees' => $terminees,
            'haute' => $haute,
            'moyenne' => $moyenne,
            'basse' => $basse,
            'en_retard' => $enRetard,
            'taux_completion' => $tauxCompletion,
        ]);
    }
}