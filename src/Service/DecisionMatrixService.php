<?php

namespace App\Service;

use App\Entity\Offre;

class DecisionMatrixService
{
    /**
     * Calcule le meilleur choix parmi une liste d'offres
     * 
     * @param array<Offre> $offres
     * @return array<array{offre: Offre, score: float, isBest: bool}>|null
     */
    public function getRecommendation(array $offres): ?array
    {
        if (empty($offres)) return null;

        // 1. Trouver les valeurs min/max pour normaliser les scores
        $minPrix = (float) min(array_map(fn(Offre $o) => $o->getPrix(), $offres));
        $minDelai = (int) min(array_map(fn(Offre $o) => $o->getDelaiTransportJours(), $offres));

        $scoredOffres = [];

        foreach ($offres as $offre) {
            // Logique : Plus le prix est bas, plus le score est haut (60% du poids)
            $scorePrix = ($minPrix / (float) $offre->getPrix()) * 60;

            // Logique : Plus le délai est court, plus le score est haut (40% du poids)
            $scoreDelai = ($minDelai / (int) $offre->getDelaiTransportJours()) * 40;

            $totalScore = round($scorePrix + $scoreDelai, 2);

            $scoredOffres[] = [
                'offre' => $offre,
                'score' => $totalScore,
                'isBest' => false
            ];
        }

        // 2. Trier par score décroissant
        usort($scoredOffres, fn(array $a, array $b) => $b['score'] <=> $a['score']);
        
        // Marquer le premier comme étant le meilleur
        // Correction de l'erreur "empty.variable" : On utilise l'accès direct puisque
        // le test empty($offres) au début garantit que scoredOffres ne sera pas vide ici.
        $scoredOffres[0]['isBest'] = true;

        return $scoredOffres;
    }
}