<?php
namespace App\Service;

use App\Entity\Offre;

class DecisionMatrixService
{
    /**
     * Calcule le meilleur choix parmi une liste d'offres
     */
    public function getRecommendation(array $offres): ?array
    {
        if (empty($offres)) return null;

        // 1. Trouver les valeurs min/max pour normaliser les scores
        $minPrix = min(array_map(fn($o) => $o->getPrix(), $offres));
        $minDelai = min(array_map(fn($o) => $o->getDelaiTransportJours(), $offres));

        $scoredOffres = [];

        foreach ($offres as $offre) {
            // Logique IA : Plus le prix est bas, plus le score est haut (60% du poids)
            // Formule : (Prix Min / Prix Actuel) * 60
            $scorePrix = ($minPrix / $offre->getPrix()) * 60;

            // Logique IA : Plus le délai est court, plus le score est haut (40% du poids)
            // Formule : (Délai Min / Délai Actuel) * 40
            $scoreDelai = ($minDelai / $offre->getDelaiTransportJours()) * 40;

            $totalScore = round($scorePrix + $scoreDelai, 2);

            $scoredOffres[] = [
                'offre' => $offre,
                'score' => $totalScore,
                'isBest' => false
            ];
        }

        // 2. Trier par score décroissant
        usort($scoredOffres, fn($a, $b) => $b['score'] <=> $a['score']);
        
        // Marquer le premier comme étant le meilleur
        if (!empty($scoredOffres)) {
            $scoredOffres[0]['isBest'] = true;
        }

        return $scoredOffres;
    }
}