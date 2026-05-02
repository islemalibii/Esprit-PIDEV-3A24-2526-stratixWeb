<?php

namespace App\Service;

class ExchangeRateService
{
    public function convertir(float $montant, string $de, string $vers): float
    {
        $taux = [
            'TND' => ['USD' => 3.0, 'EUR' => 3.2],
            'USD' => ['TND' => 0.33, 'EUR' => 0.92],
            'EUR' => ['TND' => 0.31, 'USD' => 1.09],
        ];
        
        if (isset($taux[$de][$vers])) {
            return $montant / $taux[$de][$vers];
        }
        
        return $montant;
    }
}