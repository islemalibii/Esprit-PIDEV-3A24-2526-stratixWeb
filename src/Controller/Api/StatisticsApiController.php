<?php

namespace App\Controller\Api;

use App\Repository\TacheRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticsApiController extends AbstractController
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

        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'A_FAIRE')   $aFaire++;
            if ($tache->getStatut() === 'EN_COURS')  $enCours++;
            if ($tache->getStatut() === 'TERMINEE')  $terminees++;
            if ($tache->getPriorite() === 'HAUTE')   $haute++;
            if ($tache->getPriorite() === 'MOYENNE') $moyenne++;
            if ($tache->getPriorite() === 'BASSE')   $basse++;

            if (
                $tache->getDeadline() &&
                $tache->getDeadline() < $today &&
                $tache->getStatut() !== 'TERMINEE'
            ) {
                $enRetard++;
            }
        }

        $tauxCompletion = $total > 0 ? round(($terminees / $total) * 100) : 0;

        return $this->render('admin/statistics/index.html.twig', [
            'total'           => $total,
            'a_faire'         => $aFaire,
            'en_cours'        => $enCours,
            'terminees'       => $terminees,
            'haute'           => $haute,
            'moyenne'         => $moyenne,
            'basse'           => $basse,
            'en_retard'       => $enRetard,
            'taux_completion' => $tauxCompletion,
        ]);
    }
}